<?php

namespace Tests\Feature\Publications;

use App\Enums\LocationPrecision;
use App\Enums\PublicationStatus;
use App\Models\Organization;
use App\Models\Publication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class PublicationVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_publication_is_public(): void
    {
        $publication = Publication::factory()->published()->create();

        $this->get(route('publications.show', $publication->slug))
            ->assertOk()
            ->assertSee($publication->title)
            ->assertSee('<link rel="canonical" href="'.route('publications.show', $publication->slug).'">', false);
    }

    #[TestWith(['draft'])]
    #[TestWith(['pending'])]
    #[TestWith(['rejected'])]
    public function test_unpublished_publication_is_not_public(string $status): void
    {
        $publication = Publication::factory()->withStatus(PublicationStatus::from($status))->create();

        $this->get(route('publications.show', $publication->slug))->assertNotFound();
    }

    #[TestWith(['archived'])]
    #[TestWith(['expired'])]
    public function test_withdrawn_publication_returns_410(string $status): void
    {
        $publication = Publication::factory()->withStatus(PublicationStatus::from($status))->create();

        $this->get(route('publications.show', $publication->slug))
            ->assertStatus(410)
            ->assertDontSee($publication->description);
    }

    public function test_published_but_past_expiry_date_is_not_publicly_visible(): void
    {
        $publication = Publication::factory()->published()->create(['expires_at' => now()->subMinute()]);

        $this->assertFalse($publication->isPubliclyVisible());
        $this->assertSame(0, Publication::publiclyVisible()->count());
        $this->get(route('publications.show', $publication->slug))->assertNotFound();
    }

    public function test_deleted_publication_returns_410(): void
    {
        $publication = Publication::factory()->published()->create();
        $publication->delete();

        $this->get(route('publications.show', $publication->slug))->assertStatus(410);
    }

    public function test_author_can_view_their_pending_publication_marked_noindex(): void
    {
        $user = User::factory()->create();
        $publication = Publication::factory()->for($user, 'author')->pending()->create();

        $this->actingAs($user)->get(route('publications.show', $publication->slug))
            ->assertOk()
            ->assertSee('noindex', false);
    }

    public function test_private_contact_details_and_exact_address_are_hidden(): void
    {
        $author = User::factory()->create(['email' => 'secret@example.org', 'phone' => '0612345678', 'show_email' => false, 'show_phone' => false]);
        $publication = Publication::factory()->for($author, 'author')->published()->create([
            'address' => '12 rue Secrète',
            'location_precision' => LocationPrecision::Commune,
        ]);

        $this->get(route('publications.show', $publication->slug))
            ->assertOk()
            ->assertDontSee('secret@example.org')
            ->assertDontSee('0612345678')
            ->assertDontSee('12 rue Secrète');
    }

    public function test_public_coordinates_respect_the_precision(): void
    {
        $publication = Publication::factory()->published()->create([
            'latitude' => 49.123456, 'longitude' => -1.098765,
            'location_precision' => LocationPrecision::Approximate,
        ]);
        $this->assertSame(['lat' => 49.12, 'lng' => -1.1], $publication->publicCoordinates());

        $publication->location_precision = LocationPrecision::Commune;
        $this->assertSame(['lat' => $publication->commune->latitude, 'lng' => $publication->commune->longitude], $publication->publicCoordinates());
    }

    public function test_attachment_is_downloadable_only_when_the_publication_is_visible(): void
    {
        Storage::fake('local');
        $publication = Publication::factory()->pending()->create();
        $attachment = $publication->attachments()->create([
            'path' => UploadedFile::fake()->create('programme.pdf', 20, 'application/pdf')->store("publications/{$publication->id}/attachments", 'local'),
            'original_name' => 'programme.pdf',
            'mime_type' => 'application/pdf',
            'size' => 20480,
        ]);

        $this->get(route('publications.attachment', [$publication, $attachment]))->assertNotFound();

        $publication->forceFill(['status' => PublicationStatus::Published, 'expires_at' => now()->addDay()])->save();
        $this->get(route('publications.attachment', [$publication, $attachment]))
            ->assertOk()
            ->assertDownload('programme.pdf');
    }

    public function test_organization_page_lists_only_visible_publications(): void
    {
        $publication = Publication::factory()->published()->for(Organization::factory())->create(['title' => 'Annonce visible']);
        Publication::factory()->pending()->create(['organization_id' => $publication->organization_id, 'title' => 'Annonce en attente']);

        $this->get(route('organizations.show', $publication->organization))
            ->assertSee('Annonce visible')
            ->assertDontSee('Annonce en attente');
    }
}
