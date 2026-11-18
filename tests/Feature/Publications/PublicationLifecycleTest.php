<?php

namespace Tests\Feature\Publications;

use App\Enums\PublicationStatus;
use App\Models\Publication;
use App\Models\User;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicationLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_user_submission_goes_to_moderation(): void
    {
        $user = User::factory()->create();
        $publication = Publication::factory()->for($user, 'author')->create();

        $this->actingAs($user)->post(route('account.publications.submit', $publication))
            ->assertRedirect(route('account.publications.preview', $publication));

        $publication->refresh();
        $this->assertSame(PublicationStatus::Pending, $publication->status);
        $this->assertNotNull($publication->submitted_at);
        $this->assertFalse($publication->isPubliclyVisible());
    }

    public function test_trusted_user_publishes_immediately_for_the_configured_duration(): void
    {
        $this->freezeSecond();
        app(Settings::class)->set(['publication_duration_days' => 45]);
        $user = User::factory()->create(['is_trusted' => true]);
        $publication = Publication::factory()->for($user, 'author')->create();

        $this->actingAs($user)->post(route('account.publications.submit', $publication));

        $publication->refresh();
        $this->assertSame(PublicationStatus::Published, $publication->status);
        $this->assertTrue($publication->published_at->equalTo(now()));
        $this->assertTrue($publication->expires_at->equalTo(now()->addDays(45)));
    }

    public function test_a_published_publication_cannot_be_submitted_again(): void
    {
        $user = User::factory()->create();
        $publication = Publication::factory()->for($user, 'author')->published()->create();

        $this->actingAs($user)->post(route('account.publications.submit', $publication))
            ->assertSessionHasErrors('status');
    }

    public function test_sensitive_edit_of_a_published_publication_returns_to_moderation(): void
    {
        $user = User::factory()->create();
        $publication = Publication::factory()->for($user, 'author')->published()->create();

        $this->actingAs($user)->put(route('account.publications.update', $publication), $this->payloadFor($publication, ['title' => 'Un tout nouveau titre']))
            ->assertSessionHasNoErrors();

        $this->assertSame(PublicationStatus::Pending, $publication->fresh()->status);
    }

    public function test_non_sensitive_edit_keeps_the_publication_online(): void
    {
        $user = User::factory()->create();
        $publication = Publication::factory()->for($user, 'author')->published()->create();

        $this->actingAs($user)->put(route('account.publications.update', $publication), $this->payloadFor($publication, ['meta_title' => 'Titre SEO']));

        $this->assertSame(PublicationStatus::Published, $publication->fresh()->status);
    }

    public function test_sensitive_edit_stays_online_when_the_setting_is_disabled(): void
    {
        app(Settings::class)->set(['publication_edits_require_moderation' => false]);
        $user = User::factory()->create();
        $publication = Publication::factory()->for($user, 'author')->published()->create();

        $this->actingAs($user)->put(route('account.publications.update', $publication), $this->payloadFor($publication, ['title' => 'Un tout nouveau titre']));

        $this->assertSame(PublicationStatus::Published, $publication->fresh()->status);
    }

    public function test_author_can_archive_and_renew(): void
    {
        $this->freezeSecond();
        $user = User::factory()->create();
        $publication = Publication::factory()->for($user, 'author')->published()->create();

        $this->actingAs($user)->post(route('account.publications.archive', $publication));
        $this->assertSame(PublicationStatus::Archived, $publication->fresh()->status);

        $this->actingAs($user)->post(route('account.publications.renew', $publication));
        $publication->refresh();
        $this->assertSame(PublicationStatus::Published, $publication->status);
        $this->assertTrue($publication->expires_at->equalTo(now()->addDays(app(Settings::class)->int('publication_duration_days'))));
    }

    public function test_a_never_published_draft_cannot_be_renewed(): void
    {
        $user = User::factory()->create();
        $publication = Publication::factory()->for($user, 'author')->create();

        $this->actingAs($user)->post(route('account.publications.renew', $publication))->assertSessionHasErrors('status');
        $this->assertSame(PublicationStatus::Draft, $publication->fresh()->status);
    }

    /**
     * @return array<string, mixed>
     */
    private function payloadFor(Publication $publication, array $overrides): array
    {
        return array_merge([
            'title' => $publication->title,
            'description' => $publication->description,
            'publication_type_id' => $publication->publication_type_id,
            'category_id' => $publication->category_id,
            'nature' => $publication->nature->value,
            'origin' => $publication->origin->value,
            'commune_id' => $publication->commune_id,
            'location_precision' => $publication->location_precision->value,
            'contact_form_enabled' => '1',
        ], $overrides);
    }
}
