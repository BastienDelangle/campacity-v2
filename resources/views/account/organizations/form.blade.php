@php($editing = $organization->exists)
<x-layouts.app :title="$editing ? $organization->name : 'Nouvelle organisation'">
    <div class="mx-auto max-w-3xl px-4 py-10 sm:px-6">
        <x-page-header :title="$editing ? $organization->name : 'Nouvelle organisation'" kicker="Mes organisations" :kicker-url="route('account.organizations.index')">
            @if ($editing)
                <x-slot:actions>
                    <a href="{{ route('organizations.show', $organization) }}" class="btn-secondary">Voir la fiche publique</a>
                </x-slot:actions>
            @endif
        </x-page-header>

        <form method="POST" enctype="multipart/form-data" novalidate
              action="{{ $editing ? route('account.organizations.update', $organization) : route('account.organizations.store') }}">
            @csrf
            @if ($editing) @method('PUT') @endif

            <section class="card mt-8 p-6" aria-labelledby="identite">
                <h2 id="identite" class="mb-4 text-lg font-bold">Identité</h2>
                <x-form.input name="name" label="Nom" :value="$organization->name" maxlength="150" required />
                <x-form.select name="type" label="Type d'organisation" required>
                    @foreach ($types as $type)
                        <option value="{{ $type->value }}" @selected(old('type', $organization->type?->value) === $type->value)>{{ $type->label() }}</option>
                    @endforeach
                </x-form.select>
                <x-form.textarea name="description" label="Présentation et activité" :value="$organization->description" rows="6" maxlength="5000" />

                <div class="mb-4">
                    <span class="form-label">Logo</span>
                    <div class="flex items-center gap-4">
                        @if ($editing)<x-organization-logo :organization="$organization" class="h-16 w-16 text-2xl" />@endif
                        <div class="min-w-0 flex-1">
                            <input id="logo" type="file" name="logo" accept="image/jpeg,image/png,image/webp" class="block w-full text-sm file:mr-3 file:rounded-full file:border-0 file:bg-lime file:px-4 file:py-2 file:font-bold file:text-brand" aria-describedby="logo-help">
                            <p id="logo-help" class="form-help">JPG, PNG ou WebP, 2 Mo maximum.</p>
                            @error('logo')<p class="form-error">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    @if ($editing && $organization->logo_path)
                        <x-form.checkbox name="remove_logo" label="Supprimer le logo actuel" class="mt-3" />
                    @endif
                </div>
            </section>

            <section class="card mt-6 p-6" aria-labelledby="localisation">
                <h2 id="localisation" class="mb-4 text-lg font-bold">Localisation</h2>
                <x-form.input name="address" label="Adresse" :value="$organization->address" maxlength="255" autocomplete="street-address" />
                <x-form.select name="commune_id" label="Commune" placeholder="— Choisir une commune —" help="Le territoire est déduit de la commune.">
                    @foreach ($communes->groupBy(fn ($commune) => $commune->territory?->name ?? 'Hors territoire') as $territory => $group)
                        <optgroup label="{{ $territory }}">
                            @foreach ($group as $commune)
                                <option value="{{ $commune->id }}" @selected((string) old('commune_id', $organization->commune_id) === (string) $commune->id)>{{ $commune->displayName() }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </x-form.select>
            </section>

            <section class="card mt-6 p-6" aria-labelledby="coordonnees">
                <h2 id="coordonnees" class="mb-1 text-lg font-bold">Coordonnées</h2>
                <p class="mb-4 text-sm text-muted">L'e-mail et le téléphone ne sont affichés publiquement que si vous le choisissez.</p>
                <div class="grid gap-x-4 sm:grid-cols-2">
                    <div>
                        <x-form.input name="email" type="email" label="E-mail" :value="$organization->email" autocomplete="email" />
                        <x-form.checkbox name="show_email" label="Afficher l'e-mail sur la fiche publique" :checked="$organization->show_email" />
                    </div>
                    <div>
                        <x-form.input name="phone" type="tel" label="Téléphone" :value="$organization->phone" autocomplete="tel" />
                        <x-form.checkbox name="show_phone" label="Afficher le téléphone sur la fiche publique" :checked="$organization->show_phone" />
                    </div>
                </div>
                <x-form.input name="website" type="url" label="Site internet" :value="$organization->website" placeholder="https://" class="mt-2" />

                @php($links = old('links', $organization->links ?? []))
                <fieldset class="mt-2">
                    <legend class="form-label">Liens externes <span class="font-normal text-muted">(réseaux sociaux, billetterie…)</span></legend>
                    @for ($i = 0; $i < \App\Http\Requests\OrganizationRequest::MAX_LINKS; $i++)
                        <div class="mb-2 grid gap-2 sm:grid-cols-[1fr_2fr]">
                            <input type="text" name="links[{{ $i }}][label]" value="{{ $links[$i]['label'] ?? '' }}" placeholder="Libellé" maxlength="60" class="form-input" aria-label="Libellé du lien {{ $i + 1 }}">
                            <input type="url" name="links[{{ $i }}][url]" value="{{ $links[$i]['url'] ?? '' }}" placeholder="https://" class="form-input" aria-label="Adresse du lien {{ $i + 1 }}">
                        </div>
                        @error("links.$i.url")<p class="form-error mb-2">{{ $message }}</p>@enderror
                    @endfor
                </fieldset>
            </section>

            <div class="mt-6 flex justify-end">
                <button type="submit" class="btn-primary">{{ $editing ? 'Enregistrer' : "Créer l'organisation" }}</button>
            </div>
        </form>

        @if ($editing)
            @include('account.organizations.partials.members')

            @can('delete', $organization)
                <section class="card mt-6 border-red-200 p-6" aria-labelledby="suppression">
                    <h2 id="suppression" class="text-lg font-bold text-red-800">Supprimer l'organisation</h2>
                    <p class="mt-1 text-sm text-muted">La fiche publique ne sera plus accessible.</p>
                    <form method="POST" action="{{ route('account.organizations.destroy', $organization) }}" class="mt-4" onsubmit="return confirm('Supprimer définitivement cette organisation ?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-danger">Supprimer</button>
                    </form>
                </section>
            @endcan
        @endif
    </div>
</x-layouts.app>
