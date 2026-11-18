<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\User;
use App\Models\VisibilityProduct;

class VisibilityProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::ManageVisibility->value);
    }

    public function view(User $user, VisibilityProduct $model): bool
    {
        return $user->can(Permission::ManageVisibility->value);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::ManageVisibility->value);
    }

    public function update(User $user, VisibilityProduct $model): bool
    {
        return $user->can(Permission::ManageVisibility->value);
    }

    /**
     * Pas de suppression si des achats y sont rattachés (historique) : désactiver plutôt.
     */
    public function delete(User $user, VisibilityProduct $model): bool
    {
        return $user->can(Permission::ManageVisibility->value) && ! $model->purchases()->exists();
    }
}
