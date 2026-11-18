<?php

namespace Tests\Feature\Moderation;

use App\Enums\PublicationStatus;
use App\Enums\ReportStatus;
use App\Enums\Role;
use App\Filament\Resources\PublicationReports\Pages\ManagePublicationReports;
use App\Models\Publication;
use App\Models\PublicationReport;
use App\Models\User;
use App\Notifications\NewPublicationReport;
use App\Support\SpamGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\Concerns\SubmitsPublicForms;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase, SubmitsPublicForms;

    public function test_a_visitor_can_report_a_publication_and_moderators_are_alerted(): void
    {
        Notification::fake();
        $moderator = User::factory()->create()->assignRole(Role::Moderator->value);
        $publication = Publication::factory()->published()->create();

        $this->post(route('publications.report', $publication), [
            ...$this->humanFormFields(),
            'reason' => 'fraud',
            'comment' => 'Demande de virement avant toute visite.',
        ])->assertRedirect(route('publications.show', $publication->slug).'#signaler')
            ->assertSessionHas('report_status');

        $report = PublicationReport::sole();
        $this->assertSame(ReportStatus::Open, $report->status);
        $this->assertSame('Demande de virement avant toute visite.', $report->comment);
        $this->assertNotNull($report->created_at);
        $this->assertNotSame('127.0.0.1', $report->ip_hash);
        Notification::assertSentTo($moderator, NewPublicationReport::class);
    }

    public function test_logged_in_user_can_report(): void
    {
        $user = User::factory()->create();
        $publication = Publication::factory()->published()->create();

        $this->actingAs($user)->post(route('publications.report', $publication), [...$this->humanFormFields(), 'reason' => 'spam']);

        $this->assertTrue(PublicationReport::sole()->reporter->is($user));
    }

    public function test_the_same_visitor_cannot_report_twice(): void
    {
        $publication = Publication::factory()->published()->create();

        $this->post(route('publications.report', $publication), [...$this->humanFormFields(), 'reason' => 'spam']);
        $this->post(route('publications.report', $publication), [...$this->humanFormFields(), 'reason' => 'spam']);

        $this->assertSame(1, PublicationReport::count());
    }

    public function test_other_reason_requires_a_comment(): void
    {
        $publication = Publication::factory()->published()->create();

        $this->post(route('publications.report', $publication), [...$this->humanFormFields(), 'reason' => 'other'])
            ->assertSessionHasErrorsIn('report', 'comment');
    }

    public function test_bots_are_blocked_by_the_honeypot_and_the_minimum_delay(): void
    {
        $publication = Publication::factory()->published()->create();

        $this->post(route('publications.report', $publication), [...$this->humanFormFields(), SpamGuard::HONEYPOT => 'http://spam.test', 'reason' => 'spam'])
            ->assertSessionHasErrorsIn('report', 'form');
        $this->post(route('publications.report', $publication), [SpamGuard::TOKEN => SpamGuard::token(), 'reason' => 'spam'])
            ->assertSessionHasErrorsIn('report', 'form');

        $this->assertSame(0, PublicationReport::count());
    }

    public function test_unpublished_publication_cannot_be_reported(): void
    {
        $publication = Publication::factory()->pending()->create();

        $this->post(route('publications.report', $publication), [...$this->humanFormFields(), 'reason' => 'spam'])->assertNotFound();
    }

    public function test_moderator_can_resolve_a_report_by_suspending_the_publication(): void
    {
        $moderator = User::factory()->create()->assignRole(Role::Moderator->value);
        $report = PublicationReport::factory()->create();
        $this->actingAs($moderator);

        Livewire::test(ManagePublicationReports::class)
            ->assertCanSeeTableRecords([$report])
            ->callTableAction('resolve', $report, data: ['suspend' => true, 'reason' => 'Contenu frauduleux confirmé.', 'resolution_note' => 'Vérifié.']);

        $report->refresh();
        $this->assertSame(ReportStatus::Resolved, $report->status);
        $this->assertTrue($report->handler->is($moderator));
        $this->assertSame(PublicationStatus::Suspended, $report->publication->fresh()->status);
    }

    public function test_moderator_can_dismiss_a_report(): void
    {
        $report = PublicationReport::factory()->create();
        $this->actingAs(User::factory()->create()->assignRole(Role::Moderator->value));

        Livewire::test(ManagePublicationReports::class)->callTableAction('dismiss', $report);

        $this->assertSame(ReportStatus::Dismissed, $report->fresh()->status);
        $this->assertSame(PublicationStatus::Published, $report->publication->fresh()->status);
    }
}
