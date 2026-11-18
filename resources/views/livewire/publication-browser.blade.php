<div>
    <form wire:submit="$refresh" role="search" class="rounded-2xl border border-line bg-white p-4 shadow-[0_8px_25px_#1a3b2210] sm:p-5" aria-label="Rechercher des publications">
        <div class="flex flex-col gap-3 sm:flex-row">
            <label for="recherche" class="sr-only">Rechercher</label>
            <input id="recherche" type="search" wire:model.live.debounce.400ms="search" placeholder="Rechercher : gîte, stage, maraîchage, commune…"
                   class="form-input flex-1" autocomplete="off">
            <button type="submit" class="btn-primary">Rechercher</button>
        </div>

        <div class="mt-4 grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-6">
            <div>
                <label for="f-categorie" class="mb-1 block text-xs font-bold text-[#58685b]">Catégorie</label>
                <select id="f-categorie" wire:model.live="category" class="form-input py-2 text-sm">
                    <option value="">Toutes</option>
                    @foreach ($this->categories as $cat)
                        <option value="{{ $cat->slug }}">{{ $cat->name }}</option>
                        @foreach ($cat->children as $child)
                            <option value="{{ $child->slug }}">— {{ $child->name }}</option>
                        @endforeach
                    @endforeach
                </select>
            </div>

            @unless (in_array($preset, ['urban', 'rural'], true))
                <div>
                    <label for="f-origine" class="mb-1 block text-xs font-bold text-[#58685b]">Origine</label>
                    <select id="f-origine" wire:model.live="origin" class="form-input py-2 text-sm">
                        <option value="">Toutes</option>
                        @foreach ($origins as $o)
                            <option value="{{ $o->value }}">{{ $o->label() }}</option>
                        @endforeach
                    </select>
                </div>
            @endunless

            <div>
                <label for="f-nature" class="mb-1 block text-xs font-bold text-[#58685b]">Nature</label>
                <select id="f-nature" wire:model.live="nature" class="form-input py-2 text-sm">
                    <option value="">Offres et demandes</option>
                    @foreach ($natures as $n)
                        <option value="{{ $n->value }}">{{ $n->label() }}s</option>
                    @endforeach
                </select>
            </div>

            @if ($preset !== 'agenda')
                <div>
                    <label for="f-type" class="mb-1 block text-xs font-bold text-[#58685b]">Type</label>
                    <select id="f-type" wire:model.live="type" class="form-input py-2 text-sm">
                        <option value="">Tous</option>
                        @foreach ($this->types as $t)
                            <option value="{{ $t->slug }}">{{ $t->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            @if ($this->territories->count() > 1)
                <div>
                    <label for="f-territoire" class="mb-1 block text-xs font-bold text-[#58685b]">Territoire</label>
                    <select id="f-territoire" wire:model.live="territory" class="form-input py-2 text-sm">
                        <option value="">Tous</option>
                        @foreach ($this->territories as $t)
                            <option value="{{ $t->slug }}">{{ $t->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="relative" wire:key="commune-filter-{{ $territory }}"
                 x-data="communePicker(@js(route('communes.search', array_filter(['territoire' => $territory]))), @js($this->selectedCommune))"
                 @click.outside="open = false"
                 @commune-chosen="$wire.set('commune', String($event.detail.id))"
                 @commune-cleared="$wire.set('commune', '')">
                <label for="f-commune" class="mb-1 block text-xs font-bold text-[#58685b]">Commune</label>
                <input id="f-commune" type="search" autocomplete="off" placeholder="Nom ou code postal" class="form-input py-2 text-sm"
                       role="combobox" aria-autocomplete="list" aria-controls="f-commune-list" :aria-expanded="open.toString()"
                       x-model="query" @input.debounce.250ms="search()" @keydown.down.prevent="move(1)" @keydown.up.prevent="move(-1)"
                       @keydown.enter.prevent="confirm()" @keydown.escape="open = false">
                <ul id="f-commune-list" role="listbox" x-show="open" x-cloak class="absolute z-20 mt-1 max-h-64 w-full min-w-56 overflow-auto rounded-lg border border-line bg-white py-1 shadow-lg">
                    <template x-for="(result, index) in results" :key="result.id">
                        <li role="option" :aria-selected="(index === active).toString()" @mousedown.prevent="choose(result)" @mouseenter="active = index"
                            :class="index === active ? 'bg-lime' : ''" class="cursor-pointer px-3 py-2 text-sm" x-text="result.label"></li>
                    </template>
                    <li x-show="results.length === 0" class="px-3 py-2 text-sm text-muted">Aucune commune trouvée.</li>
                </ul>
            </div>
        </div>
    </form>

    <div class="mt-5 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-muted" aria-live="polite">
            <span wire:loading.remove>{{ trans_choice('{0} Aucun résultat|{1} :count résultat|[2,*] :count résultats', $this->publications->total()) }}</span>
            <span wire:loading>Recherche…</span>
            @if ($this->hasActiveFilters)
                · <button type="button" wire:click="resetFilters" class="font-semibold text-brand underline">Effacer les filtres</button>
            @endif
        </p>
        <div class="flex items-center gap-3">
            @if ($view === 'liste')
                <label for="f-tri" class="sr-only">Trier par</label>
                <select id="f-tri" wire:model.live="sort" class="form-input w-auto py-2 text-sm">
                    <option value="">Tri par défaut</option>
                    <option value="recent">Plus récentes</option>
                    @if ($search !== '')<option value="relevance">Pertinence</option>@endif
                    <option value="upcoming">Événements à venir</option>
                </select>
            @endif
            <div class="flex rounded-xl border border-[#ccd7cc] bg-white p-1" role="group" aria-label="Mode d'affichage">
                <button type="button" wire:click="$set('view', 'liste')" @class(['rounded-lg px-3 py-1.5 text-sm font-bold', 'bg-brand text-white' => $view === 'liste']) aria-pressed="{{ $view === 'liste' ? 'true' : 'false' }}">Liste</button>
                <button type="button" wire:click="$set('view', 'carte')" @class(['rounded-lg px-3 py-1.5 text-sm font-bold', 'bg-brand text-white' => $view === 'carte']) aria-pressed="{{ $view === 'carte' ? 'true' : 'false' }}">Carte</button>
            </div>
        </div>
    </div>

    @if ($view === 'carte')
        <div wire:key="map-view" class="mt-4">
            <div wire:ignore x-data="publicationMap(@js($this->mapCenter()), @js($initialMarkers), @js(config('campacity.map')))"
                 class="h-[380px] overflow-hidden rounded-2xl border border-[#dae2d8] bg-[#e9eee6] sm:h-[480px]"
                 role="region" aria-label="Carte des publications">
                <div x-ref="map" class="h-full w-full"></div>
            </div>
            <p class="mt-2 text-xs text-muted">Les positions affichées respectent le niveau de précision choisi par chaque auteur. Les publications sans localisation ne figurent pas sur la carte.</p>
        </div>
    @else
        <div wire:key="list-view">
            @if ($this->publications->isEmpty())
                <div class="card mt-4 p-8 text-center">
                    <p class="font-bold">Aucune publication ne correspond à votre recherche.</p>
                    <p class="mt-1 text-sm text-muted">Essayez d'élargir vos critères ou de retirer un filtre.</p>
                    @if ($this->hasActiveFilters)
                        <button type="button" wire:click="resetFilters" class="btn-secondary mt-4">Effacer les filtres</button>
                    @endif
                </div>
            @else
                <div class="mt-4 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($this->publications as $publication)
                        <x-publication-card :publication="$publication" wire:key="pub-{{ $publication->id }}" />
                    @endforeach
                </div>
                <div class="mt-8">{{ $this->publications->links() }}</div>
            @endif
        </div>
    @endif
</div>
