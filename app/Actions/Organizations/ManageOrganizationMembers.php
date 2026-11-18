<?php

namespace App\Actions\Organizations;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Règles de gestion des responsables d'une organisation.
 * Une organisation garde toujours au moins un propriétaire.
 */
class ManageOrganizationMembers
{
    public function add(Organization $organization, string $email, OrganizationRole $role): User
    {
        $user = User::query()
            ->where('email', $email)
            ->whereNull('anonymized_at')
            ->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'member_email' => "Aucun compte Campacity n'est associé à cette adresse. La personne doit d'abord créer son compte.",
            ]);
        }

        if ($organization->members()->whereKey($user->id)->exists()) {
            throw ValidationException::withMessages([
                'member_email' => 'Cette personne est déjà responsable de cette organisation.',
            ]);
        }

        $organization->members()->attach($user, ['role' => $role->value]);

        return $user;
    }

    public function changeRole(Organization $organization, User $member, OrganizationRole $role): void
    {
        DB::transaction(function () use ($organization, $member, $role) {
            if ($role !== OrganizationRole::Owner) {
                $this->ensureAnotherOwnerRemains($organization, $member);
            }

            $organization->members()->updateExistingPivot($member->id, ['role' => $role->value]);
        });
    }

    public function remove(Organization $organization, User $member): void
    {
        DB::transaction(function () use ($organization, $member) {
            $this->ensureAnotherOwnerRemains($organization, $member);
            $organization->members()->detach($member->id);
        });
    }

    private function ensureAnotherOwnerRemains(Organization $organization, User $member): void
    {
        $isOwner = $organization->roleOf($member) === OrganizationRole::Owner;
        $otherOwners = $organization->owners()->lockForUpdate()->whereKeyNot($member->id)->count();

        if ($isOwner && $otherOwners === 0) {
            throw ValidationException::withMessages([
                'member' => "L'organisation doit conserver au moins un propriétaire.",
            ]);
        }
    }
}
