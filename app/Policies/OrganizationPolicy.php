<?php

namespace App\Policies;

use App\Enums\OrganizationRole;
use App\Enums\Permission;
use App\Models\Organization;
use App\Models\User;

class OrganizationPolicy
{
    /**
     * Liste complète (back-office). Chaque utilisateur voit les siennes via son espace.
     */
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::ManageOrganizations->value);
    }

    /**
     * Fiche publique : visible si active, sinon réservée aux membres et gestionnaires.
     */
    public function view(?User $user, Organization $organization): bool
    {
        if ($organization->is_active) {
            return true;
        }

        return $user !== null && ($this->isMember($user, $organization) || $user->can(Permission::ManageOrganizations->value));
    }

    public function create(User $user): bool
    {
        return $user->hasVerifiedEmail();
    }

    public function update(User $user, Organization $organization): bool
    {
        return $this->isMember($user, $organization) || $user->can(Permission::ManageOrganizations->value);
    }

    /**
     * Ajout et retrait de responsables : réservé aux propriétaires.
     */
    public function manageMembers(User $user, Organization $organization): bool
    {
        return $organization->roleOf($user) === OrganizationRole::Owner
            || $user->can(Permission::ManageOrganizations->value);
    }

    public function delete(User $user, Organization $organization): bool
    {
        return $this->manageMembers($user, $organization);
    }

    public function restore(User $user, Organization $organization): bool
    {
        return $user->can(Permission::ManageOrganizations->value);
    }

    public function forceDelete(User $user, Organization $organization): bool
    {
        return $user->can(Permission::ManageOrganizations->value);
    }

    private function isMember(User $user, Organization $organization): bool
    {
        return $organization->roleOf($user) !== null;
    }
}
