<?php

namespace App\Http\Requests;

use App\Enums\LocationPrecision;
use App\Enums\PublicationNature;
use App\Enums\PublicationOrigin;
use App\Models\Publication;
use App\Models\PublicationType;
use App\Support\Settings;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Validator;

class PublicationRequest extends FormRequest
{
    public const ATTACHMENT_TYPES = ['pdf', 'jpg', 'jpeg', 'png', 'odt', 'docx'];

    public function authorize(): bool
    {
        $publication = $this->publication();

        return $publication
            ? $this->user()->can('update', $publication)
            : $this->user()->can('create', Publication::class);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'contact_form_enabled' => $this->boolean('contact_form_enabled'),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $publication = $this->publication();
        $isEvent = (bool) PublicationType::whereKey($this->integer('publication_type_id'))->value('is_event');

        return [
            'title' => ['required', 'string', 'min:5', 'max:150'],
            'description' => ['required', 'string', 'min:20', 'max:10000'],
            'publication_type_id' => ['required', 'integer', Rule::exists('publication_types', 'id')->where('is_active', true)],
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')->where('is_active', true)],
            'nature' => ['required', Rule::enum(PublicationNature::class)],
            'origin' => ['required', Rule::enum(PublicationOrigin::class)],
            'organization_id' => ['nullable', 'integer', Rule::in($this->user()->organizations()->pluck('organizations.id')->all())],
            'commune_id' => ['required', 'integer', Rule::exists('communes', 'id')],
            'address' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
            'location_precision' => ['required', Rule::enum(LocationPrecision::class)],
            'contact_form_enabled' => ['boolean'],
            'meta_title' => ['nullable', 'string', 'max:70'],
            'meta_description' => ['nullable', 'string', 'max:170'],

            'event' => [$isEvent ? 'required' : 'nullable', 'array'],
            'event.start_date' => [Rule::requiredIf($isEvent), 'nullable', 'date_format:Y-m-d'],
            'event.start_time' => ['nullable', 'date_format:H:i'],
            'event.end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:event.start_date'],
            'event.end_time' => ['nullable', 'date_format:H:i'],
            'event.venue' => ['nullable', 'string', 'max:255'],
            'event.organizer' => ['nullable', 'string', 'max:255'],
            'event.program' => ['nullable', 'string', 'max:5000'],
            'event.registration_url' => ['nullable', 'url:http,https', 'max:255'],

            'images' => ['nullable', 'array'],
            'images.*' => [File::image()->types(['jpg', 'jpeg', 'png', 'webp'])->max(5 * 1024)->dimensions(Rule::dimensions()->maxWidth(6000)->maxHeight(6000))],
            'image_alts' => ['nullable', 'array'],
            'image_alts.*' => ['nullable', 'string', 'max:150'],
            'remove_images' => ['nullable', 'array'],
            'remove_images.*' => ['integer', Rule::exists('publication_images', 'id')->where('publication_id', $publication?->id ?? 0)],

            'attachments' => ['nullable', 'array'],
            'attachments.*' => [File::types(self::ATTACHMENT_TYPES)->max(5 * 1024)],
            'remove_attachments' => ['nullable', 'array'],
            'remove_attachments.*' => ['integer', Rule::exists('publication_attachments', 'id')->where('publication_id', $publication?->id ?? 0)],
        ];
    }

    /**
     * Limites du nombre de fichiers, en tenant compte de ceux déjà présents.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $settings = app(Settings::class);
            $publication = $this->publication();

            $images = ($publication?->images()->count() ?? 0) - count($this->input('remove_images', [])) + count($this->file('images', []));
            if ($images > ($max = $settings->int('publication_max_images'))) {
                $validator->errors()->add('images', "Vous pouvez joindre au maximum {$max} images.");
            }

            $attachments = ($publication?->attachments()->count() ?? 0) - count($this->input('remove_attachments', [])) + count($this->file('attachments', []));
            if ($attachments > ($max = $settings->int('publication_max_attachments'))) {
                $validator->errors()->add('attachments', "Vous pouvez joindre au maximum {$max} documents.");
            }

            $event = $this->input('event', []);
            if (! empty($event['start_date']) && empty($event['end_date']) && ! empty($event['start_time']) && ! empty($event['end_time'])
                && $event['end_time'] < $event['start_time']) {
                $validator->errors()->add('event.end_time', "L'heure de fin doit être postérieure à l'heure de début.");
            }
        }];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'publication_type_id' => 'type de publication',
            'category_id' => 'catégorie',
            'nature' => 'nature',
            'origin' => 'origine',
            'organization_id' => 'organisation',
            'commune_id' => 'commune',
            'address' => 'adresse',
            'location_precision' => 'précision de la localisation',
            'meta_title' => 'titre pour les moteurs de recherche',
            'meta_description' => 'description pour les moteurs de recherche',
            'event.start_date' => 'date de début',
            'event.start_time' => 'heure de début',
            'event.end_date' => 'date de fin',
            'event.end_time' => 'heure de fin',
            'event.venue' => 'lieu',
            'event.organizer' => 'organisateur',
            'event.program' => 'programme',
            'event.registration_url' => "lien d'inscription",
            'images.*' => 'image',
            'attachments.*' => 'document',
        ];
    }

    public function publication(): ?Publication
    {
        $publication = $this->route('publication');

        return $publication instanceof Publication ? $publication : null;
    }
}
