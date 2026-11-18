@php
    $editing = $publication->exists;
    $event = $publication->event;
    $eventTypeIds = $types->where('is_event', true)->pluck('id')->map(fn ($id) => (string) $id)->values();
@endphp
<x-layouts.app :title="$editing ? 'Modifier la publication' : 'Nouvelle publication'">
    <div class="mx-auto max-w-3xl px-4 py-10 sm:px-6">
        <x-page-header :title="$editing ? 'Modifier la publication' : 'Publier une offre ou une demande'" kicker="Mes publications" :kicker-url="route('account.publications.index')" />

        @if ($editing && $publication->status === \App\Enums\PublicationStatus::Published && ! auth()->user()->canPublishWithoutModeration() && app(\App\Support\Settings::class)->bool('publication_edits_require_moderation'))
            <p class="mt-6 rounded-xl border border-[#eedba8] bg-[#fff9e8] px-4 py-3 text-sm text-[#7a6233]">Cette publication est en ligne : si vous modifiez son titre, sa description, son type, sa catégorie, sa nature, son origine, son adresse ou ses images, elle repassera en modération avant d'être de nouveau visible.</p>
        @endif

        @if ($errors->any())
            <div class="mt-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
                Le formulaire contient {{ trans_choice(':count erreur|:count erreurs', $errors->count()) }}. Merci de vérifier les champs signalés.
            </div>
        @endif

        <form method="POST" enctype="multipart/form-data" novalidate
              action="{{ $editing ? route('account.publications.update', $publication) : route('account.publications.store') }}"
              x-data="{ typeId: @js((string) old('publication_type_id', $publication->publication_type_id)), eventTypes: @js($eventTypeIds) }">
            @csrf
            @if ($editing) @method('PUT') @endif

            <section class="card mt-8 p-6" aria-labelledby="quoi">
                <h2 id="quoi" class="mb-4 text-lg font-bold">Votre publication</h2>

                <fieldset class="mb-4">
                    <legend class="form-label">Je… <span class="text-red-700" aria-hidden="true">*</span></legend>
                    <div class="grid gap-2 sm:grid-cols-2">
                        @foreach ($natures as $nature)
                            <label class="flex cursor-pointer items-start gap-2 rounded-xl border border-line bg-white p-3 has-[:checked]:border-brand has-[:checked]:bg-lime/40">
                                <input type="radio" name="nature" value="{{ $nature->value }}" class="mt-1 text-brand" @checked(old('nature', $publication->nature?->value) === $nature->value)>
                                <span><strong>{{ $nature->label() }}</strong><span class="block text-sm text-muted">{{ $nature->description() }}</span></span>
                            </label>
                        @endforeach
                    </div>
                    @error('nature')<p class="form-error">{{ $message }}</p>@enderror
                </fieldset>

                <div class="grid gap-x-4 sm:grid-cols-2">
                    <x-form.select name="publication_type_id" label="Type de publication" placeholder="— Choisir —" x-model="typeId" required>
                        @foreach ($types as $type)
                            <option value="{{ $type->id }}" @selected((string) old('publication_type_id', $publication->publication_type_id) === (string) $type->id)>{{ $type->name }}</option>
                        @endforeach
                    </x-form.select>
                    <x-form.select name="category_id" label="Catégorie" placeholder="— Choisir —" required>
                        @foreach ($categories as $category)
                            @if ($category->children->isEmpty())
                                <option value="{{ $category->id }}" @selected((string) old('category_id', $publication->category_id) === (string) $category->id)>{{ $category->name }}</option>
                            @else
                                <optgroup label="{{ $category->name }}">
                                    <option value="{{ $category->id }}" @selected((string) old('category_id', $publication->category_id) === (string) $category->id)>{{ $category->name }} (général)</option>
                                    @foreach ($category->children as $child)
                                        <option value="{{ $child->id }}" @selected((string) old('category_id', $publication->category_id) === (string) $child->id)>{{ $child->name }}</option>
                                    @endforeach
                                </optgroup>
                            @endif
                        @endforeach
                    </x-form.select>
                </div>

                <x-form.input name="title" label="Titre" :value="$publication->title" maxlength="150" required help="Soyez précis : c'est la première chose que les visiteurs liront." />
                <x-form.textarea name="description" label="Description" :value="$publication->description" rows="8" maxlength="10000" required help="Ce que vous proposez ou recherchez, pour qui, quand, à quelles conditions…" />
            </section>

            <section class="card mt-6 p-6" aria-labelledby="ou">
                <h2 id="ou" class="mb-4 text-lg font-bold">Localisation</h2>
                <fieldset class="mb-4">
                    <legend class="form-label">Origine <span class="text-red-700" aria-hidden="true">*</span></legend>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($origins as $origin)
                            <label class="flex cursor-pointer items-center gap-2 rounded-full border border-line bg-white px-4 py-2 text-sm has-[:checked]:border-brand has-[:checked]:bg-lime/40">
                                <input type="radio" name="origin" value="{{ $origin->value }}" class="text-brand" @checked(old('origin', $publication->origin?->value) === $origin->value)> {{ $origin->label() }}
                            </label>
                        @endforeach
                    </div>
                    @error('origin')<p class="form-error">{{ $message }}</p>@enderror
                </fieldset>

                <x-form.commune-picker :commune="$publication->commune" required />
                <x-form.input name="address" label="Adresse" :value="$publication->address" maxlength="255" autocomplete="street-address" />
                @php($point = ['lat' => old('latitude', $publication->latitude) !== null ? (float) old('latitude', $publication->latitude) : null, 'lng' => old('longitude', $publication->longitude) !== null ? (float) old('longitude', $publication->longitude) : null])
                <div class="mb-4" x-data="locationPicker(@js($point), @js($mapCenter), @js(config('campacity.map')))">
                    <span class="form-label">Position sur la carte <span class="font-normal text-muted">(facultatif)</span></span>
                    <p class="form-help mb-2">Cliquez sur la carte pour placer un repère, déplacez-le si besoin. Sans repère, le centre de la commune est utilisé.</p>
                    <div wire:ignore class="h-64 overflow-hidden rounded-xl border border-line"><div x-ref="map" class="h-full w-full"></div></div>
                    <input type="hidden" name="latitude" :value="lat ?? ''">
                    <input type="hidden" name="longitude" :value="lng ?? ''">
                    <button type="button" x-show="lat !== null" x-cloak @click="clear()" class="mt-2 text-sm font-semibold text-red-700 hover:underline">Retirer le repère</button>
                    @error('latitude')<p class="form-error">{{ $message }}</p>@enderror
                </div>
                <x-form.select name="location_precision" label="Ce qui est affiché publiquement" help="Choisissez « Commune uniquement » si vous ne souhaitez pas révéler l'adresse.">
                    @foreach ($precisions as $precision)
                        <option value="{{ $precision->value }}" @selected(old('location_precision', $publication->location_precision?->value) === $precision->value)>{{ $precision->label() }}</option>
                    @endforeach
                </x-form.select>
            </section>

            <section class="card mt-6 p-6" aria-labelledby="evenement-titre" x-show="eventTypes.includes(typeId)" x-cloak>
                <h2 id="evenement-titre" class="mb-4 text-lg font-bold">Informations sur l'événement</h2>
                <div class="grid gap-x-4 sm:grid-cols-2">
                    <x-form.input name="event[start_date]" id="event_start_date" type="date" label="Date de début" :value="$event?->starts_at?->format('Y-m-d')" x-bind:required="eventTypes.includes(typeId)" />
                    <x-form.input name="event[start_time]" id="event_start_time" type="time" label="Heure de début" :value="$event && $event->starts_at->format('H:i') !== '00:00' ? $event->starts_at->format('H:i') : null" />
                    <x-form.input name="event[end_date]" id="event_end_date" type="date" label="Date de fin" :value="$event?->ends_at?->format('Y-m-d')" />
                    <x-form.input name="event[end_time]" id="event_end_time" type="time" label="Heure de fin" :value="$event?->ends_at?->format('H:i')" />
                </div>
                <x-form.input name="event[venue]" id="event_venue" label="Lieu" :value="$event?->venue" maxlength="255" />
                <x-form.input name="event[organizer]" id="event_organizer" label="Organisateur" :value="$event?->organizer" maxlength="255" />
                <x-form.textarea name="event[program]" id="event_program" label="Programme ou informations complémentaires" :value="$event?->program" rows="4" maxlength="5000" />
                <x-form.input name="event[registration_url]" id="event_registration_url" type="url" label="Lien d'inscription" :value="$event?->registration_url" placeholder="https://" />
            </section>

            <section class="card mt-6 p-6" aria-labelledby="medias">
                <h2 id="medias" class="mb-1 text-lg font-bold">Photos et documents</h2>
                <p class="mb-4 text-sm text-muted">{{ $maxImages }} photos maximum (JPG, PNG ou WebP, 5 Mo chacune). La première sert de vignette.</p>

                @if ($editing && $publication->images->isNotEmpty())
                    <ul class="mb-4 grid gap-3 sm:grid-cols-2">
                        @foreach ($publication->images as $image)
                            <li class="rounded-xl border border-line p-3">
                                <img src="{{ $image->url() }}" alt="" class="h-32 w-full rounded-lg object-cover">
                                <label for="alt-{{ $image->id }}" class="form-label mt-2 text-xs">Description de l'image (accessibilité)</label>
                                <input id="alt-{{ $image->id }}" type="text" name="image_alts[{{ $image->id }}]" value="{{ old("image_alts.{$image->id}", $image->alt) }}" maxlength="150" class="form-input py-1.5 text-sm">
                                <label class="mt-2 inline-flex items-center gap-2 text-sm text-red-800"><input type="checkbox" name="remove_images[]" value="{{ $image->id }}" class="rounded"> Supprimer</label>
                            </li>
                        @endforeach
                    </ul>
                @endif
                <label for="images" class="form-label">Ajouter des photos</label>
                <input id="images" type="file" name="images[]" multiple accept="image/jpeg,image/png,image/webp" class="block w-full text-sm file:mr-3 file:rounded-full file:border-0 file:bg-lime file:px-4 file:py-2 file:font-bold file:text-brand">
                @error('images')<p class="form-error">{{ $message }}</p>@enderror
                @foreach ($errors->get('images.*') as $messages)<p class="form-error">{{ $messages[0] }}</p>@endforeach

                <div class="mt-6">
                    @if ($editing && $publication->attachments->isNotEmpty())
                        <ul class="mb-3 space-y-1 text-sm">
                            @foreach ($publication->attachments as $attachment)
                                <li class="flex items-center justify-between gap-3">
                                    <span class="truncate">{{ $attachment->original_name }} <span class="text-muted">({{ $attachment->humanSize() }})</span></span>
                                    <label class="inline-flex shrink-0 items-center gap-1 text-red-800"><input type="checkbox" name="remove_attachments[]" value="{{ $attachment->id }}" class="rounded"> Supprimer</label>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                    <label for="attachments" class="form-label">Documents <span class="font-normal text-muted">({{ $maxAttachments }} maximum : {{ strtoupper(implode(', ', $attachmentTypes)) }}, 5 Mo)</span></label>
                    <input id="attachments" type="file" name="attachments[]" multiple accept=".{{ implode(',.', $attachmentTypes) }}" class="block w-full text-sm file:mr-3 file:rounded-full file:border-0 file:bg-lime file:px-4 file:py-2 file:font-bold file:text-brand">
                    @error('attachments')<p class="form-error">{{ $message }}</p>@enderror
                    @foreach ($errors->get('attachments.*') as $messages)<p class="form-error">{{ $messages[0] }}</p>@endforeach
                </div>
            </section>

            <section class="card mt-6 p-6" aria-labelledby="contact-titre">
                <h2 id="contact-titre" class="mb-4 text-lg font-bold">Publier au nom de…</h2>
                @if ($organizations->isNotEmpty())
                    <x-form.select name="organization_id" label="Auteur affiché" help="Les coordonnées publiques affichées seront celles de l'organisation choisie, ou les vôtres.">
                        <option value="">Moi-même ({{ auth()->user()->name }})</option>
                        @foreach ($organizations as $organization)
                            <option value="{{ $organization->id }}" @selected((string) old('organization_id', $publication->organization_id) === (string) $organization->id)>{{ $organization->name }}</option>
                        @endforeach
                    </x-form.select>
                @else
                    <p class="mb-4 text-sm text-muted">Vous publiez en votre nom. <a href="{{ route('account.organizations.create') }}" class="text-brand underline">Créez une organisation</a> pour publier en son nom.</p>
                @endif
                <x-form.checkbox name="contact_form_enabled" label="Permettre aux visiteurs de me contacter via un formulaire" help="Votre adresse e-mail n'est jamais révélée." :checked="$publication->contact_form_enabled" />
                <p class="text-sm text-muted">Les coordonnées affichées se règlent dans <a href="{{ route('account.profile') }}" class="text-brand underline">mes informations</a> ou sur la fiche de l'organisation.</p>
            </section>

            <details class="card mt-6 p-6" @if($errors->hasAny(['meta_title', 'meta_description'])) open @endif>
                <summary class="cursor-pointer font-bold">Référencement (facultatif)</summary>
                <div class="mt-4">
                    <x-form.input name="meta_title" label="Titre pour les moteurs de recherche" :value="$publication->meta_title" maxlength="70" help="70 caractères maximum. Par défaut : le titre de la publication." />
                    <x-form.textarea name="meta_description" label="Description pour les moteurs de recherche" :value="$publication->meta_description" rows="2" maxlength="170" help="170 caractères maximum. Par défaut : le début de la description." />
                </div>
            </details>

            <div class="mt-6 flex flex-wrap justify-end gap-3">
                <a href="{{ $editing ? route('account.publications.preview', $publication) : route('account.publications.index') }}" class="btn-secondary">Annuler</a>
                <button type="submit" class="btn-primary">Enregistrer et prévisualiser</button>
            </div>
        </form>

        @if ($editing)
            @can('delete', $publication)
                <form method="POST" action="{{ route('account.publications.destroy', $publication) }}" class="mt-8 text-right" onsubmit="return confirm('Supprimer définitivement cette publication ?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-sm font-semibold text-red-700 hover:underline">Supprimer cette publication</button>
                </form>
            @endcan
        @endif
    </div>
</x-layouts.app>
