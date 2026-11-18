<?php

namespace Tests\Feature\Browse;

use App\Models\Commune;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CommuneSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_autocomplete_returns_matching_communes_by_name_or_postal_code(): void
    {
        $commune = Commune::factory()->create(['name' => 'Coutances', 'postal_code' => '50200']);
        Commune::factory()->create(['name' => 'Avranches', 'postal_code' => '50300']);

        $this->getJson(route('communes.search', ['q' => 'cout']))
            ->assertOk()
            ->assertExactJson([[
                'id' => $commune->id,
                'label' => 'Coutances (50200)',
                'territory' => $commune->territory->name,
                'lat' => $commune->latitude,
                'lng' => $commune->longitude,
            ]]);

        $this->getJson(route('communes.search', ['q' => '5030']))->assertJsonCount(1)->assertJsonPath('0.label', 'Avranches (50300)');
        $this->getJson(route('communes.search', ['q' => 'a']))->assertExactJson([]);
    }

    public function test_autocomplete_can_be_restricted_to_a_territory(): void
    {
        $inside = Commune::factory()->create(['name' => 'Bayeux']);
        Commune::factory()->create(['name' => 'Bayonne']);

        $this->getJson(route('communes.search', ['q' => 'bay', 'territoire' => $inside->territory->slug]))
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $inside->id);
    }

    public function test_import_command_creates_territory_and_communes_from_the_official_api(): void
    {
        Http::fake([
            'geo.api.gouv.fr/communes?*' => Http::response([
                ['nom' => 'Coutances', 'code' => '50147', 'codesPostaux' => ['50200'], 'centre' => ['type' => 'Point', 'coordinates' => [-1.4429, 49.0478]]],
                ['nom' => 'Agon-Coutainville', 'code' => '50003', 'codesPostaux' => ['50230'], 'centre' => ['type' => 'Point', 'coordinates' => [-1.5747, 49.0459]]],
            ]),
        ]);

        $this->artisan('campacity:import-communes', ['territory' => 'Pays de Coutances', '--epci' => ['200067064']])
            ->expectsOutputToContain('2 communes traitées')
            ->assertSuccessful();

        $commune = Commune::where('insee_code', '50147')->firstOrFail();
        $this->assertSame('Coutances', $commune->name);
        $this->assertSame('50200', $commune->postal_code);
        $this->assertSame(49.0478, $commune->latitude);
        $this->assertSame('Pays de Coutances', $commune->territory->name);
        $this->assertNotNull($commune->territory->center_latitude);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'codeEpci=200067064'));

        // Réimport idempotent.
        $this->artisan('campacity:import-communes', ['territory' => 'Pays de Coutances', '--epci' => ['200067064']])->assertSuccessful();
        $this->assertSame(2, Commune::count());
    }

    public function test_import_command_requires_a_source(): void
    {
        $this->artisan('campacity:import-communes', ['territory' => 'Test'])->assertFailed();
    }
}
