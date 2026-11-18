<?php

namespace Tests\Feature\Publications;

use App\Enums\PublicationStatus;
use App\Models\Category;
use App\Models\Commune;
use App\Models\Organization;
use App\Models\Publication;
use App\Models\PublicationType;
use App\Models\User;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicationFormTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Gîte rural disponible pour séjours de télétravail',
            'description' => 'Un gîte calme, fibre optique, idéal pour des équipes urbaines en séminaire.',
            'publication_type_id' => PublicationType::factory()->create()->id,
            'category_id' => Category::factory()->create()->id,
            'nature' => 'offer',
            'origin' => 'rural',
            'commune_id' => Commune::factory()->create()->id,
            'location_precision' => 'commune',
            'contact_form_enabled' => '1',
        ], $overrides);
    }

    public function test_create_form_renders(): void
    {
        PublicationType::factory()->create(['name' => 'Annonce test']);

        $this->actingAs(User::factory()->create())
            ->get(route('account.publications.create'))
            ->assertOk()
            ->assertSee('Annonce test');
    }

    public function test_user_can_create_a_draft_and_is_sent_to_preview(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('account.publications.store'), $this->validPayload());

        $publication = Publication::firstOrFail();
        $response->assertRedirect(route('account.publications.preview', $publication));
        $this->assertSame(PublicationStatus::Draft, $publication->status);
        $this->assertTrue($publication->author->is($user));
        $this->assertSame('gite-rural-disponible-pour-sejours-de-teletravail', $publication->slug);

        $this->actingAs($user)->get(route('account.publications.preview', $publication))
            ->assertOk()
            ->assertSee($publication->title)
            ->assertSee('Soumettre');
    }

    public function test_required_fields_are_validated(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('account.publications.store'), [])
            ->assertSessionHasErrors([
                'title' => 'Le champ titre est obligatoire.',
                'description', 'publication_type_id', 'category_id', 'nature', 'origin', 'commune_id',
            ]);
    }

    public function test_event_type_requires_a_start_date_and_stores_event_details(): void
    {
        $user = User::factory()->create();
        $eventType = PublicationType::factory()->event()->create();

        $this->actingAs($user)
            ->post(route('account.publications.store'), $this->validPayload(['publication_type_id' => $eventType->id]))
            ->assertSessionHasErrors(['event.start_date' => 'Le champ date de début est obligatoire.']);

        $this->actingAs($user)->post(route('account.publications.store'), $this->validPayload([
            'publication_type_id' => $eventType->id,
            'event' => [
                'start_date' => '2027-03-14', 'start_time' => '09:30', 'end_time' => '17:00',
                'venue' => 'Salle des fêtes', 'registration_url' => 'https://inscription.test',
            ],
        ]))->assertSessionHasNoErrors();

        $event = Publication::firstOrFail()->event;
        $this->assertSame('2027-03-14 09:30', $event->starts_at->format('Y-m-d H:i'));
        $this->assertSame('2027-03-14 17:00', $event->ends_at->format('Y-m-d H:i'));
        $this->assertSame('Salle des fêtes', $event->venue);
    }

    public function test_images_are_resized_and_reencoded_as_webp(): void
    {
        Storage::fake('public');
        config(['campacity.image_max_width' => 800]);

        $this->actingAs(User::factory()->create())->post(route('account.publications.store'), $this->validPayload([
            'images' => [UploadedFile::fake()->image('photo.jpg', 2000, 1000)],
        ]))->assertSessionHasNoErrors();

        $image = Publication::firstOrFail()->images->first();
        $this->assertStringEndsWith('.webp', $image->path);
        $this->assertSame(800, $image->width);
        $this->assertSame(400, $image->height);
        Storage::disk('public')->assertExists($image->path);
    }

    public function test_image_count_is_limited_by_the_setting(): void
    {
        Storage::fake('public');
        app(Settings::class)->set(['publication_max_images' => 1]);

        $this->actingAs(User::factory()->create())->post(route('account.publications.store'), $this->validPayload([
            'images' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg')],
        ]))->assertSessionHasErrors(['images' => 'Vous pouvez joindre au maximum 1 images.']);
    }

    public function test_user_can_only_publish_on_behalf_of_their_own_organizations(): void
    {
        $user = User::factory()->create();
        $foreign = Organization::factory()->create();

        $this->actingAs($user)
            ->post(route('account.publications.store'), $this->validPayload(['organization_id' => $foreign->id]))
            ->assertSessionHasErrors('organization_id');

        $mine = Organization::factory()->ownedBy($user)->create();
        $this->actingAs($user)
            ->post(route('account.publications.store'), $this->validPayload(['organization_id' => $mine->id]))
            ->assertSessionHasNoErrors();
    }

    public function test_another_user_cannot_edit_or_preview_a_publication(): void
    {
        $publication = Publication::factory()->create();
        $stranger = User::factory()->create();

        $this->actingAs($stranger)->get(route('account.publications.edit', $publication))->assertForbidden();
        $this->actingAs($stranger)->get(route('account.publications.preview', $publication))->assertForbidden();
        $this->actingAs($stranger)->put(route('account.publications.update', $publication), $this->validPayload())->assertForbidden();
    }

    public function test_organization_members_can_edit_its_publications(): void
    {
        $editor = User::factory()->create();
        $organization = Organization::factory()->editedBy($editor)->create();
        $publication = Publication::factory()->create(['organization_id' => $organization->id]);

        $this->actingAs($editor)->get(route('account.publications.edit', $publication))->assertOk();
    }
}
