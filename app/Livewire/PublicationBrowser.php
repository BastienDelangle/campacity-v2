<?php

namespace App\Livewire;

use App\Enums\PublicationNature;
use App\Enums\PublicationOrigin;
use App\Models\Category;
use App\Models\Commune;
use App\Models\Publication;
use App\Models\PublicationType;
use App\Models\Territory;
use App\Queries\PublicationSearch;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Recherche, filtres, liste paginée et carte des publications.
 * Les filtres sont reflétés dans l'URL (partage, retour arrière, SEO).
 */
class PublicationBrowser extends Component
{
    use WithPagination;

    public const PRESETS = ['urban', 'rural', 'agenda'];

    /** Rubrique imposée par la page (Côté urbain, Côté rural, Formations et rendez-vous). */
    #[Locked]
    public ?string $preset = null;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'categorie', except: '')]
    public string $category = '';

    #[Url(as: 'origine', except: '')]
    public string $origin = '';

    #[Url(as: 'nature', except: '')]
    public string $nature = '';

    #[Url(as: 'territoire', except: '')]
    public string $territory = '';

    #[Url(as: 'commune', except: '')]
    public string $commune = '';

    #[Url(as: 'type', except: '')]
    public string $type = '';

    #[Url(as: 'tri', except: '')]
    public string $sort = '';

    #[Url(as: 'vue', except: 'liste')]
    public string $view = 'liste';

    public function mount(?string $preset = null): void
    {
        $this->preset = in_array($preset, self::PRESETS, true) ? $preset : null;
        $this->view = in_array($this->view, ['liste', 'carte'], true) ? $this->view : 'liste';
    }

    public function updated(string $property): void
    {
        if ($property === 'territory') {
            $this->commune = '';
        }

        if ($property !== 'view') {
            $this->resetPage();
        }

        if ($this->view === 'carte') {
            $this->dispatch('map-markers', markers: $this->markers());
        }
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'category', 'origin', 'nature', 'territory', 'commune', 'type', 'sort']);
        $this->resetPage();

        if ($this->view === 'carte') {
            $this->dispatch('map-markers', markers: $this->markers());
        }
    }

    public function searchQuery(): PublicationSearch
    {
        $origins = match ($this->preset) {
            // « Entre les deux » apparaît des deux côtés.
            'urban' => [PublicationOrigin::Urban, PublicationOrigin::Both],
            'rural' => [PublicationOrigin::Rural, PublicationOrigin::Both],
            default => array_filter([PublicationOrigin::tryFrom($this->origin)]),
        };

        $sort = $this->sort ?: ($this->preset === 'agenda' ? 'upcoming' : ($this->search !== '' ? 'relevance' : 'recent'));

        return new PublicationSearch(
            text: $this->search,
            categorySlug: $this->category ?: null,
            origins: array_values($origins),
            nature: PublicationNature::tryFrom($this->nature),
            territorySlug: $this->territory ?: null,
            communeId: ctype_digit($this->commune) ? (int) $this->commune : null,
            typeSlugs: $this->type !== '' ? [$this->type] : [],
            agenda: $this->preset === 'agenda',
            sort: $sort,
        );
    }

    /**
     * @return LengthAwarePaginator<int, Publication>
     */
    #[Computed]
    public function publications(): LengthAwarePaginator
    {
        return $this->searchQuery()->query()
            ->with(['type', 'category', 'commune', 'images', 'event'])
            ->paginate(12);
    }

    /**
     * Repères de la carte : mêmes filtres que la liste, coordonnées publiques uniquement.
     *
     * @return list<array{id: int, title: string, url: string, category: string, commune: ?string, lat: float, lng: float}>
     */
    public function markers(): array
    {
        return $this->searchQuery()->query()
            ->with(['category', 'commune'])
            ->limit((int) config('campacity.map.max_markers', 500))
            ->get()
            ->map(function (Publication $publication) {
                $point = $publication->publicCoordinates();

                return $point === null ? null : [
                    'id' => $publication->id,
                    'title' => $publication->title,
                    'url' => route('publications.show', $publication->slug),
                    'category' => $publication->category->name,
                    'commune' => $publication->commune?->name,
                    'lat' => $point['lat'],
                    'lng' => $point['lng'],
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    #[Computed]
    public function hasActiveFilters(): bool
    {
        return collect([$this->search, $this->category, $this->origin, $this->nature, $this->territory, $this->commune, $this->type])
            ->contains(fn (string $value) => $value !== '');
    }

    /**
     * @return Collection<int, Category>
     */
    #[Computed(persist: true, seconds: 300)]
    public function categories(): Collection
    {
        return Category::active()->roots()->ordered()->with(['children' => fn ($q) => $q->active()->ordered()])->get();
    }

    /**
     * @return Collection<int, Territory>
     */
    #[Computed(persist: true, seconds: 300)]
    public function territories(): Collection
    {
        return Territory::active()->orderBy('name')->get(['id', 'name', 'slug', 'center_latitude', 'center_longitude', 'default_zoom']);
    }

    /**
     * Commune actuellement filtrée (pour pré-remplir le champ de saisie).
     *
     * @return array{id: int, label: string}|null
     */
    #[Computed]
    public function selectedCommune(): ?array
    {
        $commune = ctype_digit($this->commune) ? Commune::find((int) $this->commune) : null;

        return $commune ? ['id' => $commune->id, 'label' => $commune->displayName()] : null;
    }

    /**
     * @return Collection<int, PublicationType>
     */
    #[Computed(persist: true, seconds: 300)]
    public function types(): Collection
    {
        return PublicationType::active()->ordered()->get(['id', 'name', 'slug']);
    }

    /**
     * Centre et zoom initiaux de la carte.
     *
     * @return array{lat: float, lng: float, zoom: int}
     */
    public function mapCenter(): array
    {
        $territory = $this->territories->firstWhere('slug', $this->territory)
            ?? $this->territories->first(fn ($t) => $t->center_latitude !== null);

        if ($territory?->center_latitude !== null) {
            return ['lat' => $territory->center_latitude, 'lng' => $territory->center_longitude, 'zoom' => $territory->default_zoom ?? 10];
        }

        [$lat, $lng] = config('campacity.map.default_center');

        return ['lat' => $lat, 'lng' => $lng, 'zoom' => (int) config('campacity.map.default_zoom')];
    }

    public function render(): View
    {
        return view('livewire.publication-browser', [
            'natures' => PublicationNature::cases(),
            'origins' => PublicationOrigin::cases(),
            'initialMarkers' => $this->view === 'carte' ? $this->markers() : [],
        ]);
    }
}
