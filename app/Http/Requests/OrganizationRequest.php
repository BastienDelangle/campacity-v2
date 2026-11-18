<?php

namespace App\Http\Requests;

use App\Enums\OrganizationType;
use App\Models\Organization;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class OrganizationRequest extends FormRequest
{
    public const MAX_LINKS = 5;

    public function authorize(): bool
    {
        $organization = $this->route('organization');

        return $organization instanceof Organization
            ? $this->user()->can('update', $organization)
            : $this->user()->can('create', Organization::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'type' => ['required', Rule::enum(OrganizationType::class)],
            'description' => ['nullable', 'string', 'max:5000'],
            'address' => ['nullable', 'string', 'max:255'],
            'commune_id' => ['nullable', 'integer', Rule::exists('communes', 'id')],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+().\s-]{6,30}$/'],
            'website' => ['nullable', 'url:http,https', 'max:255'],
            'show_email' => ['boolean'],
            'show_phone' => ['boolean'],
            'links' => ['nullable', 'array', 'max:'.self::MAX_LINKS],
            'links.*.label' => ['nullable', 'string', 'max:60'],
            'links.*.url' => ['nullable', 'url:http,https', 'max:255'],
            // Pas de SVG : risque d'injection de script.
            'logo' => ['nullable', File::image()->types(['jpg', 'jpeg', 'png', 'webp'])->max(2 * 1024)->dimensions(Rule::dimensions()->maxWidth(4000)->maxHeight(4000))],
            'remove_logo' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'type' => "type d'organisation",
            'commune_id' => 'commune',
            'address' => 'adresse',
            'links.*.label' => 'libellé du lien',
            'links.*.url' => 'adresse du lien',
            'logo' => 'logo',
        ];
    }
}
