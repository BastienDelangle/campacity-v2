<?php

namespace Tests\Feature\Moderation;

use App\Enums\ModerationAction;
use App\Enums\PublicationStatus;
use App\Enums\Role;
use App\Filament\Resources\Publications\Pages\ListPublications;
use App\Filament\Resources\Publications\Pages\ViewPublication;
use App\Models\Publication;
use App\Models\User;
use App\Notifications\NewPublicationToModerate;
use App\Notifications\PublicationApproved;
use App\Notifications\PublicationRejected;
use App\Notifications\PublicationSubmittedForReview;
use App\Notifications\PublicationSuspended;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class ModerationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function moderator(): User
    {
        return User::factory()->create()->assignRole(Role::Moderator->value);
    }

    public function test_submission_notifies_author_and_moderators_and_is_logged(): void
    {
        Notification::fake();
        $moderator = $this->moderator();
        $author = User::factory()->create();
        $publication = Publication::factory()->for($author, 'author')->create();

        $this->actingAs($author)->post(route('account.publications.submit', $publication));

        Notification::assertSentTo($author, PublicationSubmittedForReview::class);
        Notification::assertSentTo($moderator, NewPublicationToModerate::class);
        Notification::assertNotSentTo($author, NewPublicationToModerate::class);

        $log = $publication->moderationLogs()->first();
        $this->assertSame(ModerationAction::Submitted, $log->action);
        $this->assertSame(PublicationStatus::Draft, $log->from_status);
        $this->assertSame(PublicationStatus::Pending, $log->to_status);
        $this->assertTrue($log->user->is($author));
    }

    public function test_trusted_publication_is_logged_without_alerting_moderators(): void
    {
        Notification::fake();
        $moderator = $this->moderator();
        $author = User::factory()->create(['is_trusted' => true]);
        $publication = Publication::factory()->for($author, 'author')->create();

        $this->actingAs($author)->post(route('account.publications.submit', $publication));

        Notification::assertNothingSentTo($moderator);
        $this->assertSame(ModerationAction::AutoPublished, $publication->moderationLogs()->first()->action);
    }

    public function test_moderator_can_approve_and_the_author_is_told(): void
    {
        Notification::fake();
        $moderator = $this->moderator();
        $publication = Publication::factory()->pending()->create();

        $this->actingAs($moderator);
        Livewire::test(ListPublications::class)->callTableAction('approve', $publication)->assertNotified();

        $this->assertSame(PublicationStatus::Published, $publication->fresh()->status);
        Notification::assertSentTo($publication->author, PublicationApproved::class);
        $this->assertTrue($publication->moderationLogs()->first()->user->is($moderator));
    }

    public function test_reject_requires_a_reason_which_is_sent_to_the_author(): void
    {
        Notification::fake();
        $publication = Publication::factory()->pending()->create();
        $this->actingAs($this->moderator());

        Livewire::test(ListPublications::class)
            ->callTableAction('reject', $publication, data: ['reason' => ''])
            ->assertHasTableActionErrors(['reason' => 'required']);

        Livewire::test(ListPublications::class)
            ->callTableAction('reject', $publication, data: ['reason' => 'Les coordonnées ne doivent pas figurer dans le titre.']);

        $publication->refresh();
        $this->assertSame(PublicationStatus::Rejected, $publication->status);
        $this->assertSame('Les coordonnées ne doivent pas figurer dans le titre.', $publication->rejection_reason);
        Notification::assertSentTo($publication->author, PublicationRejected::class, fn ($n) => $n->reason === 'Les coordonnées ne doivent pas figurer dans le titre.');
        $this->assertSame(ModerationAction::Rejected, $publication->moderationLogs()->first()->action);
    }

    public function test_rejected_publication_is_not_public_and_can_be_corrected_and_resubmitted(): void
    {
        $author = User::factory()->create();
        $publication = Publication::factory()->for($author, 'author')->withStatus(PublicationStatus::Rejected)->create(['rejection_reason' => 'Trop vague']);

        $this->get(route('publications.show', $publication->slug))->assertNotFound();
        $this->actingAs($author)->get(route('account.publications.preview', $publication))->assertSee('Trop vague');

        $this->actingAs($author)->post(route('account.publications.submit', $publication));
        $this->assertSame(PublicationStatus::Pending, $publication->fresh()->status);
    }

    public function test_suspended_publication_is_hidden_and_locked_for_its_author(): void
    {
        Notification::fake();
        $author = User::factory()->create();
        $publication = Publication::factory()->for($author, 'author')->published()->create();
        $this->actingAs($this->moderator());

        Livewire::test(ViewPublication::class, ['record' => $publication->slug])
            ->callAction('suspend', data: ['reason' => 'Signalements multiples pour arnaque.'])
            ->assertNotified('Publication suspendue');

        $publication->refresh();
        $this->assertSame(PublicationStatus::Suspended, $publication->status);
        Notification::assertSentTo($author, PublicationSuspended::class);

        $this->post('/logout');
        $this->get(route('publications.show', $publication->slug))->assertNotFound();
        $this->actingAs($author)->get(route('account.publications.edit', $publication))->assertForbidden();
        $this->actingAs($author)->post(route('account.publications.renew', $publication))->assertSessionHasErrors('status');
        $this->actingAs($author)->post(route('account.publications.archive', $publication))->assertSessionHasErrors('status');
        $this->assertSame(PublicationStatus::Suspended, $publication->fresh()->status);
    }

    public function test_moderator_can_reinstate_a_suspended_publication(): void
    {
        $publication = Publication::factory()->published()->create();
        $publication->forceFill(['status' => PublicationStatus::Suspended, 'rejection_reason' => 'x'])->save();
        $this->actingAs($this->moderator());

        Livewire::test(ViewPublication::class, ['record' => $publication->slug])->callAction('reinstate');

        $publication->refresh();
        $this->assertSame(PublicationStatus::Published, $publication->status);
        $this->assertNull($publication->rejection_reason);
        $this->assertTrue($publication->isPubliclyVisible());
    }

    public function test_detail_page_shows_content_and_history(): void
    {
        $publication = Publication::factory()->pending()->create(['title' => 'Annonce à examiner']);
        $this->actingAs($this->moderator());

        $this->get("/admin/publications/{$publication->slug}")->assertOk()->assertSee('Annonce à examiner');
    }

    public function test_regular_user_cannot_moderate(): void
    {
        $publication = Publication::factory()->pending()->create();

        $this->actingAs(User::factory()->create()->assignRole(Role::User->value))
            ->get("/admin/publications/{$publication->slug}")
            ->assertForbidden();
    }
}
