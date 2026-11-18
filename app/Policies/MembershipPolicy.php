<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Membership;
use App\Models\User;

/**
 * Lecture et actions dédiées uniquement : pas de création/modification libre,
 * pour garantir la cohérence avec les paiements.
 */
class MembershipPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::ManageMemberships->value);
    }

    public function view(User $user, Membership $model): bool
    {
        return $user->can(Permission::ManageMemberships->value);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Membership $model): bool
    {
        return false;
    }

    public function delete(User $user, Membership $model): bool
    {
        return false;
    }

    /**
     * Enregistrement d'une adhésion réglée hors ligne.
     */
    public function record(User $user): bool
    {
        return $user->can(Permission::ManageMemberships->value);
    }
}
