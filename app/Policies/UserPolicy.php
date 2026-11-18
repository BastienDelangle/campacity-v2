<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\User;

/**
 * Gestion des comptes depuis l'administration. Un utilisateur gère son propre
 * profil via son espace (Fortify), jamais celui d'un autre.
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::ManageUsers->value);
    }

    public function view(User $user, User $model): bool
    {
        return $user->is($model) || $user->can(Permission::ManageUsers->value);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::ManageUsers->value);
    }

    public function update(User $user, User $model): bool
    {
        return $user->is($model) || $user->can(Permission::ManageUsers->value);
    }

    /**
     * Attribution des rôles : permission dédiée, et jamais sur son propre compte
     * (évite qu'un administrateur se retire ses droits par erreur).
     */
    public function assignRoles(User $user, User $model): bool
    {
        return $user->can(Permission::ManageRoles->value) && ! $user->is($model);
    }

    /**
     * Les comptes sont anonymisés, pas supprimés physiquement (conservation).
     */
    public function delete(User $user, User $model): bool
    {
        return $user->is($model) || $user->can(Permission::ManageUsers->value);
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
