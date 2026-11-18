<?php

namespace App\Queries;

use App\Enums\PublicationNature;
use App\Enums\PublicationOrigin;
use App\Models\Category;
use App\Models\Publication;
use App\Models\PublicationType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Recherche publique de publications. Point unique de filtrage : la liste
 * et la carte utilisent la même requête, donc les mêmes résultats.
 */
class PublicationSearch
{
    public const SORTS = ['recent', 'relevance', 'upcoming'];

    /**
     * @param  list<PublicationOrigin>  $origins
     * @param  list<string>  $typeSlugs
     */
    public function __construct(
        public readonly ?string $text = null,
        public readonly ?string $categorySlug = null,
        public readonly array $origins = [],
        public readonly ?PublicationNature $nature = null,
        public readonly ?string $territorySlug = null,
        public readonly ?int $communeId = null,
        public readonly array $typeSlugs = [],
        public readonly bool $agenda = false,
        public readonly bool $promotedOnly = false,
        public readonly string $sort = 'recent',
    ) {}

    /**
     * @return Builder<Publication>
     */
    public function query(): Builder
    {
        $query = Publication::query()->select('publications.*')->withVisibilityRank()->publiclyVisible();

        if ($this->promotedOnly) {
            $query->promoted();
        }

        $this->applyText($query);

        if ($this->categorySlug && ($category = Category::active()->where('slug', $this->categorySlug)->first())) {
            // Une catégorie principale inclut ses sous-catégories.
            $query->whereIn('category_id', $category->children()->pluck('id')->push($category->id));
        }

        if ($this->origins !== []) {
            $query->whereIn('origin', $this->origins);
        }

        if ($this->nature) {
            $query->where('nature', $this->nature);
        }

        if ($this->territorySlug) {
            $query->whereHas('commune.territory', fn (Builder $q) => $q->where('slug', $this->territorySlug));
        }

        if ($this->communeId) {
            $query->where('commune_id', $this->communeId);
        }

        if ($this->typeSlugs !== []) {
            $query->whereIn('publication_type_id', PublicationType::whereIn('slug', $this->typeSlugs)->select('id'));
        }

        if ($this->agenda) {
            // « Formations et rendez-vous » : types événement + catégories configurées (et leurs sous-catégories).
            $slugs = config('campacity.agenda_category_slugs', []);
            $query->where(fn (Builder $q) => $q
                ->whereHas('type', fn (Builder $t) => $t->where('is_event', true))
                ->orWhereHas('category', fn (Builder $c) => $c
                    ->whereIn('slug', $slugs)
                    ->orWhereHas('parent', fn (Builder $p) => $p->whereIn('slug', $slugs))));
        }

        $this->applySort($query);

        return $query;
    }

    private function applyText(Builder $query): void
    {
        $text = trim((string) $this->text);

        if ($text === '') {
            return;
        }

        $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $text).'%';
        $fullText = $this->booleanFullTextQuery($text);

        $query->where(function (Builder $q) use ($like, $fullText) {
            if ($fullText !== '') {
                $q->whereFullText(['title', 'description'], $fullText, ['mode' => 'boolean']);
            } else {
                $q->where('title', 'like', $like);
            }

            $q->orWhereHas('commune', fn (Builder $c) => $c->where('name', 'like', $like)->orWhere('postal_code', 'like', $like))
                ->orWhereHas('commune.territory', fn (Builder $t) => $t->where('name', 'like', $like))
                ->orWhereHas('category', fn (Builder $c) => $c->where('name', 'like', $like));
        });
    }

    /**
     * « gîte rural » → « +gîte* +rural* » : tous les mots, préfixes acceptés.
     * Les mots de moins de 3 lettres sont ignorés (taille minimale de l'index MySQL).
     */
    private function booleanFullTextQuery(string $text): string
    {
        return collect(preg_split('/[^\p{L}\p{N}]+/u', $text) ?: [])
            ->filter(fn (string $word) => Str::length($word) >= 3)
            ->take(8)
            ->map(fn (string $word) => '+'.$word.'*')
            ->implode(' ');
    }

    private function applySort(Builder $query): void
    {
        $sort = in_array($this->sort, self::SORTS, true) ? $this->sort : 'recent';

        // Les publications boostées puis mises en avant passent devant (sauf agenda chronologique).
        if ($sort !== 'upcoming') {
            $query->orderByDesc('visibility_rank');
        }

        if ($sort === 'relevance' && ($fullText = $this->booleanFullTextQuery((string) $this->text)) !== '') {
            $query->orderByRaw('MATCH(title, description) AGAINST (? IN BOOLEAN MODE) DESC', [$fullText]);
        }

        if ($sort === 'upcoming') {
            $query->leftJoin('publication_events', 'publication_events.publication_id', '=', 'publications.id')
                ->orderByRaw('publication_events.starts_at IS NULL')
                ->orderBy('publication_events.starts_at');
        }

        $query->orderByDesc('publications.published_at')->orderByDesc('publications.id');
    }
}
