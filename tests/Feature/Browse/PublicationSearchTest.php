<?php

namespace Tests\Feature\Browse;

use App\Enums\PublicationNature;
use App\Enums\PublicationOrigin;
use App\Enums\PublicationStatus;
use App\Livewire\PublicationBrowser;
use App\Models\Category;
use App\Models\Commune;
use App\Models\Publication;
use App\Models\PublicationType;
use App\Models\Territory;
use App\Queries\PublicationSearch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PublicationSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_section_pages_render(): void
    {
        foreach (['publications.index', 'browse.urban', 'browse.rural', 'browse.agenda', 'home'] as $route) {
            $this->get(route($route))->assertOk();
        }
    }

    public function test_only_publicly_visible_publications_are_listed(): void
    {
        $visible = Publication::factory()->published()->create(['title' => 'Annonce visible']);
        foreach (PublicationStatus::cases() as $status) {
            if ($status !== PublicationStatus::Published) {
                Publication::factory()->withStatus($status)->create(['title' => "Annonce {$status->value}"]);
            }
        }
        Publication::factory()->published()->create(['title' => 'Annonce échue', 'expires_at' => now()->subDay()]);

        Livewire::test(PublicationBrowser::class)
            ->assertSee('Annonce visible')
            ->assertDontSee('Annonce draft')
            ->assertDontSee('Annonce pending')
            ->assertDontSee('Annonce rejected')
            ->assertDontSee('Annonce archived')
            ->assertDontSee('Annonce expired')
            ->assertDontSee('Annonce échue')
            ->assertViewHas('initialMarkers', [])
            ->assertSet('view', 'liste');

        $this->assertSame([$visible->id], (new PublicationSearch)->query()->pluck('publications.id')->all());
    }

    public function test_combined_filters_return_the_right_results(): void
    {
        $territory = Territory::factory()->create(['slug' => 'pays-a']);
        $commune = Commune::factory()->for($territory)->create();
        $parent = Category::factory()->create(['slug' => 'terroir']);
        $child = Category::factory()->childOf($parent)->create();

        $match = Publication::factory()->published()->for($commune)->for($child)->create([
            'origin' => PublicationOrigin::Rural, 'nature' => PublicationNature::Offer,
        ]);
        // Chacune échoue sur exactement un critère.
        Publication::factory()->published()->for($commune)->for($child)->create(['origin' => PublicationOrigin::Urban, 'nature' => PublicationNature::Offer]);
        Publication::factory()->published()->for($commune)->for($child)->create(['origin' => PublicationOrigin::Rural, 'nature' => PublicationNature::Request]);
        Publication::factory()->published()->for($child)->create(['origin' => PublicationOrigin::Rural, 'nature' => PublicationNature::Offer]);
        Publication::factory()->published()->for($commune)->create(['origin' => PublicationOrigin::Rural, 'nature' => PublicationNature::Offer]);

        $component = Livewire::test(PublicationBrowser::class)
            ->set('category', 'terroir')
            ->set('origin', 'rural')
            ->set('nature', 'offer')
            ->set('territory', 'pays-a');

        $this->assertSame([$match->id], $component->instance()->publications->pluck('id')->all());

        $component->set('commune', (string) $commune->id);
        $this->assertSame([$match->id], $component->instance()->publications->pluck('id')->all());
    }

    public function test_map_markers_follow_the_active_filters_and_hide_exact_positions(): void
    {
        $commune = Commune::factory()->create(['latitude' => 49.0, 'longitude' => -1.0]);
        $rural = Publication::factory()->published()->for($commune)->create([
            'origin' => PublicationOrigin::Rural, 'latitude' => 49.123456, 'longitude' => -1.234567, 'location_precision' => 'approximate',
        ]);
        Publication::factory()->published()->for($commune)->create(['origin' => PublicationOrigin::Urban]);
        Publication::factory()->pending()->for($commune)->create(['origin' => PublicationOrigin::Rural]);

        $component = Livewire::test(PublicationBrowser::class)
            ->set('origin', 'rural')
            ->set('view', 'carte');

        $markers = $component->instance()->markers();
        $this->assertCount(1, $markers);
        $this->assertSame($rural->id, $markers[0]['id']);
        $this->assertSame(49.12, $markers[0]['lat']);
        $this->assertSame(-1.23, $markers[0]['lng']);

        $component->set('nature', $rural->nature === PublicationNature::Offer ? 'request' : 'offer')
            ->assertDispatched('map-markers', markers: []);
    }

    public function test_urban_and_rural_sections_include_publications_from_both_sides(): void
    {
        $urban = Publication::factory()->published()->create(['origin' => PublicationOrigin::Urban]);
        $rural = Publication::factory()->published()->create(['origin' => PublicationOrigin::Rural]);
        $both = Publication::factory()->published()->create(['origin' => PublicationOrigin::Both]);

        $ids = fn (string $preset) => Livewire::test(PublicationBrowser::class, ['preset' => $preset])
            ->instance()->publications->pluck('id')->sort()->values()->all();

        $this->assertSame(collect([$urban->id, $both->id])->sort()->values()->all(), $ids('urban'));
        $this->assertSame(collect([$rural->id, $both->id])->sort()->values()->all(), $ids('rural'));
    }

    public function test_agenda_lists_events_and_trainings_ordered_by_date(): void
    {
        $eventType = PublicationType::factory()->event()->create();
        $trainings = Category::firstOrCreate(['slug' => 'formations'], ['name' => 'Formations']);

        $later = Publication::factory()->published()->for($eventType, 'type')->create();
        $later->event()->create(['starts_at' => now()->addDays(10)]);
        $sooner = Publication::factory()->published()->for($eventType, 'type')->create();
        $sooner->event()->create(['starts_at' => now()->addDays(2)]);
        $training = Publication::factory()->published()->for($trainings)->create();
        Publication::factory()->published()->create();

        $ids = Livewire::test(PublicationBrowser::class, ['preset' => 'agenda'])->instance()->publications->pluck('id')->all();

        $this->assertSame([$sooner->id, $later->id, $training->id], $ids);
    }

    public function test_results_are_paginated_and_empty_state_is_shown(): void
    {
        Publication::factory()->published()->count(13)->create();

        $component = Livewire::test(PublicationBrowser::class);
        $this->assertCount(12, $component->instance()->publications);
        $this->assertSame(13, $component->instance()->publications->total());

        $component->set('search', 'zzzintrouvablezzz')
            ->assertSee('Aucune publication ne correspond à votre recherche.');
    }

    public function test_filters_are_read_from_the_url(): void
    {
        Publication::factory()->published()->create(['title' => 'Offre de séjour', 'nature' => PublicationNature::Offer]);
        Publication::factory()->published()->create(['title' => 'Demande de séjour', 'nature' => PublicationNature::Request]);

        $this->get(route('publications.index', ['nature' => 'request']))
            ->assertOk()
            ->assertSee('Demande de séjour')
            ->assertDontSee('Offre de séjour')
            ->assertSee('noindex, follow', false);
    }
}
