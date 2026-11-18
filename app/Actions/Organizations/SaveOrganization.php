<?php

namespace App\Actions\Organizations;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Crée ou met à jour une organisation et gère son logo.
 * À la création, l'auteur devient propriétaire de l'organisation.
 */
class SaveOrganization
{
    /**
     * @param  array<string, mixed>  $attributes  Données validées.
     */
    public function create(User $creator, array $attributes, ?UploadedFile $logo = null): Organization
    {
        return DB::transaction(function () use ($creator, $attributes, $logo) {
            $organization = new Organization($this->normalize($attributes));
            $organization->save();

            $organization->members()->attach($creator, ['role' => OrganizationRole::Owner->value]);

            if ($logo) {
                $this->replaceLogo($organization, $logo);
            }

            return $organization;
        });
    }

    /**
     * @param  array<string, mixed>  $attributes  Données validées.
     */
    public function update(Organization $organization, array $attributes, ?UploadedFile $logo = null, bool $removeLogo = false): Organization
    {
        $organization->fill($this->normalize($attributes))->save();

        if ($logo) {
            $this->replaceLogo($organization, $logo);
        } elseif ($removeLogo) {
            $this->deleteLogo($organization);
        }

        return $organization;
    }

    private function replaceLogo(Organization $organization, UploadedFile $logo): void
    {
        $this->deleteLogo($organization);

        $organization->forceFill([
            'logo_path' => $logo->store("organizations/{$organization->id}", 'public'),
        ])->save();
    }

    private function deleteLogo(Organization $organization): void
    {
        if ($organization->logo_path) {
            Storage::disk('public')->delete($organization->logo_path);
            $organization->forceFill(['logo_path' => null])->save();
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function normalize(array $attributes): array
    {
        $attributes['links'] = collect($attributes['links'] ?? [])
            ->filter(fn ($link) => filled($link['url'] ?? null))
            ->map(fn ($link) => ['label' => trim((string) ($link['label'] ?? '')), 'url' => trim((string) $link['url'])])
            ->values()
            ->all();

        $attributes['show_email'] = (bool) ($attributes['show_email'] ?? false);
        $attributes['show_phone'] = (bool) ($attributes['show_phone'] ?? false);

        unset($attributes['logo'], $attributes['remove_logo']);

        return $attributes;
    }
}
