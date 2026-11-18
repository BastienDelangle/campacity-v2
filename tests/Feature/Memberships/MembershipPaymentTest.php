<?php

namespace Tests\Feature\Memberships;

use App\Enums\MembershipStatus;
use App\Enums\PaymentStatus;
use App\Jobs\VerifyHelloAssoPayment;
use App\Models\Membership;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\MembershipConfirmed;
use App\Notifications\PaymentConfirmed;
use App\Payments\HelloAsso\HelloAssoPaymentVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\URL;
use Tests\Concerns\FakesHelloAsso;
use Tests\TestCase;

class MembershipPaymentTest extends TestCase
{
    use FakesHelloAsso, RefreshDatabase;

    private function orderMembership(User $user): Payment
    {
        $this->actingAs($user)->post(route('account.membership.store'), ['terms' => '1'])
            ->assertRedirect('https://www.helloasso-sandbox.com/checkout/abc');

        return Payment::sole();
    }

    public function test_joining_redirects_to_helloasso_with_a_valid_checkout_payload(): void
    {
        $this->fakeHelloAsso();
        $user = User::factory()->create(['name' => 'Camille Martin', 'email' => 'camille@example.org']);

        $payment = $this->orderMembership($user);

        $this->assertSame('9001', $payment->provider_checkout_id);
        Http::assertSent(function ($request) use ($payment) {
            if (! str_ends_with($request->url(), '/checkout-intents') || $request->method() !== 'POST') {
                return false;
            }

            return $request['totalAmount'] === 2000
                && $request['initialAmount'] === 2000
                && $request['containsDonation'] === false
                && str_starts_with($request['returnUrl'], 'https://')
                && $request['metadata'] === ['payment_id' => $payment->id, 'reference' => "CAMP-{$payment->id}"]
                && $request['payer'] === ['firstName' => 'Camille', 'lastName' => 'Martin', 'email' => 'camille@example.org'];
        });
    }

    public function test_membership_is_only_active_after_the_payment_is_verified(): void
    {
        Notification::fake();
        $this->fakeHelloAsso();
        $user = User::factory()->create();
        $payment = $this->orderMembership($user);

        $this->assertFalse($user->isMember());
        $this->assertSame(MembershipStatus::PendingPayment, Membership::sole()->status);

        $this->fakeHelloAsso($this->authorizedOrder(2000, '2026-11-18T10:00:00+01:00'));
        app(HelloAssoPaymentVerifier::class)->verify($payment);

        $membership = Membership::sole();
        $this->assertSame(MembershipStatus::Active, $membership->status);
        $this->assertSame('2026-11-18 10:00', $membership->paid_at->setTimezone('Europe/Paris')->format('Y-m-d H:i'));
        $this->assertTrue($membership->starts_at->equalTo($membership->paid_at));
        $this->assertTrue($membership->ends_at->equalTo($membership->starts_at->copy()->addYear()));
        $this->assertSame('order-555', $payment->fresh()->provider_reference);
        Notification::assertSentTo($user, MembershipConfirmed::class);
        Notification::assertNotSentTo($user, PaymentConfirmed::class);
    }

    public function test_the_browser_return_never_activates_on_its_own(): void
    {
        $this->fakeHelloAsso();
        $user = User::factory()->create();
        $payment = $this->orderMembership($user);

        $url = URL::temporarySignedRoute('payments.helloasso.return', now()->addDay(), ['payment' => $payment->id, 'type' => 'return'])
            .'&checkoutIntentId=9001&code=succeeded&orderId=555';

        $this->actingAs($user)->get($url)->assertRedirect(route('account.membership'));

        $this->assertSame(PaymentStatus::Pending, $payment->fresh()->status);
        $this->assertFalse($user->fresh()->isMember());
    }

    public function test_the_browser_return_triggers_a_server_verification(): void
    {
        $this->fakeHelloAsso();
        $user = User::factory()->create();
        $payment = $this->orderMembership($user);
        $this->fakeHelloAsso($this->authorizedOrder());

        $url = URL::temporarySignedRoute('payments.helloasso.return', now()->addDay(), ['payment' => $payment->id, 'type' => 'return'])
            .'&checkoutIntentId=9001&code=succeeded&orderId=555';
        $this->actingAs($user)->get($url)->assertSessionHas('status', 'Paiement confirmé, merci !');

        $this->assertTrue($user->fresh()->isMember());
    }

    public function test_the_return_url_must_be_signed(): void
    {
        $payment = Payment::factory()->create(['provider' => 'helloasso']);

        $this->actingAs($payment->payable->user)
            ->get(route('payments.helloasso.return', ['payment' => $payment->id, 'type' => 'return']))
            ->assertForbidden();
    }

    public function test_an_amount_mismatch_does_not_activate(): void
    {
        $this->fakeHelloAsso();
        $user = User::factory()->create();
        $payment = $this->orderMembership($user);

        $this->fakeHelloAsso($this->authorizedOrder(500));
        app(HelloAssoPaymentVerifier::class)->verify($payment);

        $this->assertSame(PaymentStatus::Pending, $payment->fresh()->status);
        $this->assertFalse($user->fresh()->isMember());
    }

    public function test_a_refused_payment_is_marked_failed(): void
    {
        $this->fakeHelloAsso();
        $payment = $this->orderMembership(User::factory()->create());

        $this->fakeHelloAsso(['id' => 1, 'amount' => ['total' => 2000], 'payments' => [['id' => 2, 'amount' => 2000, 'state' => 'Refused']]]);
        app(HelloAssoPaymentVerifier::class)->verify($payment);

        $this->assertSame(PaymentStatus::Failed, $payment->fresh()->status);
        $this->assertSame(MembershipStatus::PendingPayment, Membership::sole()->status);
    }

    public function test_webhook_queues_a_verification_for_the_matching_payment(): void
    {
        Queue::fake();
        $this->fakeHelloAsso();
        $payment = $this->orderMembership(User::factory()->create());

        $this->postJson(route('webhooks.helloasso'), [
            'eventType' => 'Order',
            'data' => ['id' => 555, 'checkoutIntentId' => 9001],
            'metadata' => ['payment_id' => $payment->id],
        ])->assertOk();

        Queue::assertPushed(VerifyHelloAssoPayment::class, fn ($job) => $job->payment->is($payment));
    }

    public function test_a_forged_webhook_cannot_activate_a_membership(): void
    {
        $this->fakeHelloAsso();
        $user = User::factory()->create();
        $payment = $this->orderMembership($user);

        // Notification falsifiée : l'API HelloAsso, elle, ne connaît aucune commande.
        $this->postJson(route('webhooks.helloasso'), [
            'eventType' => 'Payment',
            'data' => ['state' => 'Authorized', 'amount' => 2000, 'order' => ['id' => 1, 'checkoutIntentId' => 9001]],
            'metadata' => ['payment_id' => $payment->id],
        ])->assertOk();

        $this->assertSame(PaymentStatus::Pending, $payment->fresh()->status);
        $this->assertFalse($user->fresh()->isMember());
    }

    public function test_webhook_from_an_unlisted_ip_is_rejected_when_an_allowlist_is_set(): void
    {
        config(['services.helloasso.webhook_ips' => ['51.138.206.200']]);

        $this->postJson(route('webhooks.helloasso'), ['eventType' => 'Order'])->assertForbidden();
    }

    public function test_unknown_webhook_is_acknowledged(): void
    {
        $this->postJson(route('webhooks.helloasso'), ['eventType' => 'Form', 'data' => []])->assertOk();
    }

    public function test_reconciliation_verifies_pending_payments_and_abandons_old_ones(): void
    {
        $this->fakeHelloAsso();
        $user = User::factory()->create();
        $payment = $this->orderMembership($user);

        $this->travel(3)->hours();
        $this->artisan('payments:reconcile')->assertSuccessful();

        $this->assertSame(PaymentStatus::Cancelled, $payment->fresh()->status);
    }

    public function test_reconciliation_activates_a_payment_whose_notification_was_missed(): void
    {
        $this->fakeHelloAsso();
        $user = User::factory()->create();
        $this->orderMembership($user);
        $this->fakeHelloAsso($this->authorizedOrder());

        $this->artisan('payments:reconcile');

        $this->assertTrue($user->fresh()->isMember());
    }

    public function test_the_access_token_is_reused_between_calls(): void
    {
        $this->fakeHelloAsso();
        $payment = $this->orderMembership(User::factory()->create());
        app(HelloAssoPaymentVerifier::class)->verify($payment);
        app(HelloAssoPaymentVerifier::class)->verify($payment->fresh());

        Http::assertSentCount(4); // 1 jeton + 1 création + 2 lectures
    }

    public function test_checkout_is_retried_without_payer_when_helloasso_rejects_it(): void
    {
        $this->configureHelloAsso();
        Http::fake(function ($request) {
            if (str_ends_with($request->url(), '/oauth2/token')) {
                return Http::response(['access_token' => 'tok', 'expires_in' => 1799]);
            }

            return isset($request['payer'])
                ? Http::response(['errors' => [['code' => 'ArgumentInvalid', 'message' => 'Le champ prénom est invalide']]], 400)
                : Http::response(['id' => 42, 'redirectUrl' => 'https://www.helloasso-sandbox.com/checkout/abc']);
        });

        $this->actingAs(User::factory()->create(['name' => 'Admin Campacity']))
            ->post(route('account.membership.store'), ['terms' => '1'])
            ->assertRedirect('https://www.helloasso-sandbox.com/checkout/abc');

        $this->assertSame('42', Payment::sole()->provider_checkout_id);
    }

    public function test_service_unavailable_shows_a_friendly_error(): void
    {
        $this->configureHelloAsso();
        Http::fake(['*' => Http::response([], 500)]);

        $this->actingAs(User::factory()->create())
            ->post(route('account.membership.store'), ['terms' => '1'])
            ->assertRedirect(route('account.membership'))
            ->assertSessionHasErrors('payment');
    }
}
