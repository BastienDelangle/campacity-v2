<?php

namespace Tests\Feature\Visibility;

use App\Enums\PaymentStatus;
use App\Enums\PurchaseStatus;
use App\Enums\Role;
use App\Enums\VisibilityKind;
use App\Filament\Resources\VisibilityPurchases\Pages\ManageVisibilityPurchases;
use App\Livewire\PublicationBrowser;
use App\Models\Publication;
use App\Models\User;
use App\Models\VisibilityProduct;
use App\Models\VisibilityPurchase;
use App\Notifications\PaymentConfirmed;
use App\Payments\PaymentManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class VisibilityPurchaseTest extends TestCase
{
    use RefreshDatabase;

    private function featuredProduct(): VisibilityProduct
    {
        return VisibilityProduct::where('slug', 'mettre-en-avant')->firstOrFail();
    }

    public function test_a_visibility_offer_can_be_ordered_for_a_publication(): void
    {
        $author = User::factory()->create();
        $publication = Publication::factory()->for($author, 'author')->published()->create();

        $this->actingAs($author)->get(route('account.publications.visibility.create', $publication))
            ->assertOk()->assertSee('Mettre en avant')->assertSee('20,00');

        $this->actingAs($author)->post(route('account.publications.visibility.store', $publication), [
            'product_id' => $this->featuredProduct()->id,
            'terms' => '1',
        ]);

        $purchase = VisibilityPurchase::sole();
        $this->assertSame(PurchaseStatus::PendingPayment, $purchase->status);
        $this->assertTrue($purchase->publication->is($publication));
        $this->assertSame(2000, $purchase->amount_cents);
        $this->assertSame(PaymentStatus::Pending, $purchase->latestPayment()->status);

        $this->actingAs($author)->get(route('account.purchases.show', $purchase))
            ->assertOk()->assertSee('CAMP-'.$purchase->latestPayment()->id);
    }

    public function test_visibility_is_not_active_before_payment_confirmation(): void
    {
        $purchase = VisibilityPurchase::factory()->create();

        $this->assertNull($purchase->publication->fresh()->visibilityKind());
        $this->assertSame(0, Publication::promoted()->count());
    }

    public function test_payment_confirmation_activates_the_visibility_and_notifies_the_buyer(): void
    {
        Notification::fake();
        $this->freezeSecond();
        $author = User::factory()->create();
        $publication = Publication::factory()->for($author, 'author')->published()->create();
        $this->actingAs($author)->post(route('account.publications.visibility.store', $publication), ['product_id' => $this->featuredProduct()->id, 'terms' => '1']);
        $purchase = VisibilityPurchase::sole();

        app(PaymentManager::class)->markSucceeded($purchase->latestPayment(), 'VIR-123');

        $purchase->refresh();
        $this->assertSame(PurchaseStatus::Active, $purchase->status);
        $this->assertTrue($purchase->starts_at->equalTo(now()));
        $this->assertTrue($purchase->ends_at->equalTo(now()->addDays(30)));
        $this->assertSame(VisibilityKind::Featured, $publication->fresh()->visibilityKind());
        Notification::assertSentTo($author, PaymentConfirmed::class);
    }

    public function test_confirming_the_same_payment_twice_activates_once(): void
    {
        $purchase = VisibilityPurchase::factory()->create();
        $payment = app(PaymentManager::class)->create($purchase, $purchase->user, 2000, 'manual');

        app(PaymentManager::class)->markSucceeded($payment, 'A');
        $endsAt = $purchase->fresh()->ends_at;
        app(PaymentManager::class)->markSucceeded($payment, 'A');

        $this->assertTrue($purchase->fresh()->ends_at->equalTo($endsAt));
    }

    public function test_a_failed_payment_does_not_activate(): void
    {
        $purchase = VisibilityPurchase::factory()->create();
        $payment = app(PaymentManager::class)->create($purchase, $purchase->user, 2000, 'manual');

        app(PaymentManager::class)->markFailed($payment);

        $this->assertSame(PurchaseStatus::PendingPayment, $purchase->fresh()->status);
        $this->assertNull($purchase->publication->fresh()->visibilityKind());
    }

    public function test_a_second_purchase_extends_after_the_current_one(): void
    {
        $this->freezeSecond();
        $active = VisibilityPurchase::factory()->active(10)->create();
        $next = VisibilityPurchase::factory()->create([
            'publication_id' => $active->publication_id,
            'visibility_product_id' => $active->visibility_product_id,
            'kind' => $active->kind,
        ]);
        $payment = app(PaymentManager::class)->create($next, $next->user, 2000, 'manual');

        app(PaymentManager::class)->markSucceeded($payment, 'B');

        $this->assertTrue($next->fresh()->starts_at->equalTo($active->ends_at));
    }

    public function test_visibility_expires_automatically(): void
    {
        $purchase = VisibilityPurchase::factory()->active()->create();
        $publication = $purchase->publication;
        $this->assertSame(VisibilityKind::Featured, $publication->fresh()->visibilityKind());

        $this->travel(31)->days();

        $this->assertNull($publication->fresh()->visibilityKind());
        $this->artisan('visibility:expire')->assertSuccessful();
        $this->assertSame(PurchaseStatus::Expired, $purchase->fresh()->status);
    }

    public function test_promoted_publications_come_first_and_are_filterable_and_flagged_on_the_map(): void
    {
        $plain = Publication::factory()->published()->create(['published_at' => now()]);
        $featured = VisibilityPurchase::factory()->active()->create()->publication;
        $boosted = VisibilityPurchase::factory()->active()->create([
            'visibility_product_id' => VisibilityProduct::factory()->boost()->create()->id,
            'kind' => VisibilityKind::Boost,
            'ranking_weight' => 20,
        ])->publication;
        $featured->forceFill(['published_at' => now()->subWeek()])->save();

        $component = Livewire::test(PublicationBrowser::class);
        $this->assertSame([$boosted->id, $featured->id, $plain->id], $component->instance()->publications->pluck('id')->all());
        $component->assertSee('★ Boostée')->assertSee('★ Mise en avant');

        $component->set('visibility', 'mises-en-avant');
        $this->assertEqualsCanonicalizing([$boosted->id, $featured->id], $component->instance()->publications->pluck('id')->all());

        $markers = collect(Livewire::test(PublicationBrowser::class)->instance()->markers())->keyBy('id');
        $this->assertTrue($markers[$featured->id]['featured']);
        $this->assertFalse($markers[$plain->id]['featured']);
    }

    public function test_only_a_manager_of_the_publication_can_order(): void
    {
        $publication = Publication::factory()->published()->create();

        $this->actingAs(User::factory()->create())
            ->post(route('account.publications.visibility.store', $publication), ['product_id' => $this->featuredProduct()->id, 'terms' => '1'])
            ->assertForbidden();
    }

    public function test_an_offline_publication_cannot_be_promoted(): void
    {
        $author = User::factory()->create();
        $publication = Publication::factory()->for($author, 'author')->pending()->create();

        $this->actingAs($author)
            ->post(route('account.publications.visibility.store', $publication), ['product_id' => $this->featuredProduct()->id, 'terms' => '1'])
            ->assertSessionHasErrors('product');
    }

    public function test_buyer_cannot_see_someone_elses_purchase(): void
    {
        $purchase = VisibilityPurchase::factory()->create();

        $this->actingAs(User::factory()->create())->get(route('account.purchases.show', $purchase))->assertForbidden();
    }

    public function test_admin_confirms_a_manual_payment_from_the_back_office(): void
    {
        $purchase = VisibilityPurchase::factory()->create();
        app(PaymentManager::class)->create($purchase, $purchase->user, 2000, 'manual');
        $this->actingAs(User::factory()->create()->assignRole(Role::Admin->value));

        Livewire::test(ManageVisibilityPurchases::class)
            ->callTableAction('confirmPayment', $purchase, data: ['reference' => 'CHQ 0042'])
            ->assertNotified('Paiement confirmé, offre activée');

        $this->assertSame(PurchaseStatus::Active, $purchase->fresh()->status);
        $this->assertSame('manual-CHQ 0042', $purchase->latestPayment()->provider_reference);
        $this->assertNotNull($purchase->latestPayment()->confirmed_by);
    }

    public function test_moderator_cannot_confirm_payments(): void
    {
        $this->actingAs(User::factory()->create()->assignRole(Role::Moderator->value))
            ->get('/admin/visibility-purchases')
            ->assertForbidden();
    }

    public function test_buyer_can_cancel_a_pending_order(): void
    {
        $purchase = VisibilityPurchase::factory()->create();
        app(PaymentManager::class)->create($purchase, $purchase->user, 2000, 'manual');

        $this->actingAs($purchase->user)->post(route('account.purchases.cancel', $purchase))->assertRedirect(route('account.purchases.index'));

        $this->assertSame(PurchaseStatus::Cancelled, $purchase->fresh()->status);
        $this->assertSame(PaymentStatus::Cancelled, $purchase->latestPayment()->status);
    }
}
