<?php

namespace Tests\Feature\Browse;

use App\Livewire\PublicationBrowser;
use App\Models\Category;
use App\Models\Commune;
use App\Models\Publication;
use App\Models\Territory;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Recherche plein texte : l'index FULLTEXT d'InnoDB ne voit que les données
 * validées, d'où la troncature au lieu d'une transaction.
 */
class PublicationTextSearchTest extends TestCase
{
    use DatabaseTruncation;

    /**
     * Les données validées ne doivent pas fuiter dans les tests transactionnels suivants.
     */
    protected function tearDown(): void
    {
        $this->truncateTablesForAllConnections();
        $this->seed();

        parent::tearDown();
    }

    public function test_text_search_matches_title_description_commune_territory_and_category(): void
    {
        $territory = Territory::factory()->create(['name' => 'Bocage normand']);
        $commune = Commune::factory()->for($territory)->create(['name' => 'Villedieu-les-Poêles']);
        $category = Category::factory()->create(['name' => 'Artisanat']);

        $byTitle = Publication::factory()->published()->create(['title' => 'Atelier de maraîchage partagé']);
        $byDescription = Publication::factory()->published()->create(['description' => 'Nous cherchons un apiculteur pour nos ruches communales.']);
        $byCommune = Publication::factory()->published()->for($commune)->create();
        $byCategory = Publication::factory()->published()->for($category)->create();
        Publication::factory()->published()->create(['title' => 'Sans rapport', 'description' => 'Rien à voir avec la recherche.']);

        $this->assertSearchReturns('maraîchage', [$byTitle]);
        $this->assertSearchReturns('apicult', [$byDescription]);
        $this->assertSearchReturns('Villedieu', [$byCommune]);
        $this->assertSearchReturns('bocage', [$byCommune]);
        $this->assertSearchReturns('Artisanat', [$byCategory]);
    }

    /**
     * @param  list<Publication>  $expected
     */
    private function assertSearchReturns(string $text, array $expected): void
    {
        $ids = Livewire::test(PublicationBrowser::class)->set('search', $text)->instance()->publications->pluck('id')->sort()->values()->all();

        $this->assertSame(collect($expected)->pluck('id')->sort()->values()->all(), $ids, "Recherche « {$text} »");
    }
}
