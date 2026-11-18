<?php

namespace Tests\Feature\Memberships;

use App\Actions\Memberships\Memberships;
use App\Enums\MembershipStatus;
use App\Enums\Role;
use App\Filament\Resources\Memberships\Pages\ManageMemberships;
use App\Models\Membership;
use App\Models\NotificationLog;
use App\Models\User;
use App\Notifications\MembershipExpiryReminder;
use App\Payments\PaymentManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class MembershipRulesTest extends TestCase
{
    use RefreshDatabase;

    private function payNewMembership(User $user, Carbon $paidAt): Membership
    {
        $membership = $user->memberships()->create(['amount_cents' => 2000]);
        $payment = app(PaymentManager::class)->create($membership, $user, 2000, 'manual');
        app(PaymentManager::class)->markSucceeded($payment, 'ref-'.$membership->id, paidAt: $paidAt);

        return $membership->fresh();
    }

    public function test_a_membership_lasts_twelve_months_from_its_effective_date_not_the_calendar_year(): void
    {
        $membership = $this->payNewMembership(User::factory()->create(), Carbon::parse('2026-03-15 14:00'));

        $this->assertSame('2026-03-15', $membership->starts_at->toDateString());
        $this->assertSame('2027-03-15', $membership->ends_at->toDateString());
    }

    public function test_early_renewal_extends_from_the_current_expiry(): void
    {
        $user = User::factory()->create();
        Membership::factory()->for($user)->activeUntil(Carbon::parse('2027-01-10 00:00'))->create();

        $renewal = $this->payNewMembership($user, Carbon::parse('2026-12-01 09:00'));

        $this->assertSame('2027-01-10', $renewal->starts_at->toDateString());
        $this->assertSame('2028-01-10', $renewal->ends_at->toDateString());
    }

    public function test_renewal_after_expiry_starts_a_new_period_from_the_new_membership(): void
    {
        $user = User::factory()->create();
        $old = Membership::factory()->for($user)->activeUntil(Carbon::parse('2026-06-30'))->create();
        $old->update(['status' => MembershipStatus::Expired]);

        $renewal = $this->payNewMembership($user, Carbon::parse('2026-11-18 10:00'));

        $this->assertSame('2026-11-18', $renewal->starts_at->toDateString());
        $this->assertSame('2027-11-18', $renewal->ends_at->toDateString());
    }

    public function test_early_renewal_rule_is_configurable(): void
    {
        config(['campacity.membership.early_renewal_extends' => false]);
        $user = User::factory()->create();
        Membership::factory()->for($user)->activeUntil(Carbon::parse('2027-01-10'))->create();

        $renewal = $this->payNewMembership($user, Carbon::parse('2026-12-01 09:00'));

        $this->assertSame('2026-12-01', $renewal->starts_at->toDateString());
    }

    #[TestWith([30])]
    #[TestWith([7])]
    #[TestWith([0])]
    public function test_reminders_are_sent_at_the_planned_dates_and_never_twice(int $days): void
    {
        Notification::fake();
        $this->travelTo(Carbon::parse('2026-11-18 08:00'));
        $membership = Membership::factory()->activeUntil(now()->startOfDay()->addDays($days)->setTime(18, 0))->create();
        Membership::factory()->activeUntil(now()->addDays(12))->create(); // pas d'échéance de rappel

        $this->artisan('memberships:send-reminders')->assertSuccessful();
        $this->artisan('memberships:send-reminders')->assertSuccessful();

        Notification::assertSentTimes(MembershipExpiryReminder::class, 1);
        Notification::assertSentTo($membership->user, MembershipExpiryReminder::class, fn ($n) => $n->daysBefore === $days);
        $this->assertSame(1, NotificationLog::where('dedupe_key', "membership:{$membership->id}:expiry:{$days}")->count());
    }

    public function test_no_reminder_when_already_renewed(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        Membership::factory()->for($user)->activeUntil(now()->startOfDay()->addDays(7)->setTime(12, 0))->create();
        Membership::factory()->for($user)->activeUntil(now()->addDays(7)->addYear())->create();

        $this->artisan('memberships:send-reminders');

        Notification::assertNothingSent();
    }

    public function test_sent_notifications_are_logged(): void
    {
        $membership = Membership::factory()->activeUntil(now()->startOfDay()->addDays(30)->setTime(12, 0))->create();

        $this->artisan('memberships:send-reminders');

        $log = NotificationLog::where('dedupe_key', "membership:{$membership->id}:expiry:30")->sole();
        $this->assertSame('mail', $log->channel);
        $this->assertNotNull($log->sent_at);
        $this->assertTrue($log->subject->is($membership));
    }

    public function test_expired_memberships_are_marked_expired(): void
    {
        $membership = Membership::factory()->activeUntil(now()->subMinute())->create();

        $this->artisan('memberships:send-reminders');

        $this->assertSame(MembershipStatus::Expired, $membership->fresh()->status);
        $this->assertFalse($membership->user->isMember());
    }

    public function test_admin_records_an_offline_membership(): void
    {
        $member = User::factory()->create();
        $this->actingAs(User::factory()->create()->assignRole(Role::Admin->value));

        Livewire::test(ManageMemberships::class)
            ->callAction('record', data: ['user_id' => $member->id, 'paid_at' => '2026-11-10', 'amount' => 20, 'reference' => 'Espèces AG'])
            ->assertNotified('Adhésion enregistrée');

        $membership = $member->memberships()->sole();
        $this->assertSame(MembershipStatus::Active, $membership->status);
        $this->assertSame('manual', $membership->source);
        $this->assertSame('2026-11-10', $membership->starts_at->toDateString());
        $this->assertSame('manual-Espèces AG', $membership->latestPayment()->provider_reference);
    }

    public function test_member_sees_status_and_history(): void
    {
        $user = User::factory()->create();
        Membership::factory()->for($user)->activeUntil(Carbon::parse('2027-05-01'))->create();

        $this->actingAs($user)->get(route('account.membership'))
            ->assertOk()
            ->assertSee('Adhérent(e)')
            ->assertSee('1 mai 2027')
            ->assertSee('Renouveler mon adhésion');
    }

    public function test_memberships_admin_is_restricted(): void
    {
        $this->actingAs(User::factory()->create()->assignRole(Role::Moderator->value))->get('/admin/memberships')->assertForbidden();
        $this->actingAs(User::factory()->create()->assignRole(Role::Admin->value))->get('/admin/memberships')->assertOk();
        $this->actingAs(User::factory()->create()->assignRole(Role::Admin->value))->get('/admin/notification-logs')->assertOk();
    }

    public function test_a_new_order_cancels_the_previous_unpaid_one(): void
    {
        config(['payments.membership_gateway' => 'manual']);
        $user = User::factory()->create();
        $first = app(Memberships::class)->order($user);
        app(Memberships::class)->order($user);

        $this->assertSame(MembershipStatus::Cancelled, $first->fresh()->status);
    }
}
