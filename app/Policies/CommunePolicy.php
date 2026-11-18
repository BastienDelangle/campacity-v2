<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Commune;
use App\Models\User;

class CommunePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::ManageTerritories->value);
    }

    public function view(User $user, Commune $model): bool
    {
        return $user->can(Permission::ManageTerritories->value);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::ManageTerritories->value);
    }

    public function update(User $user, Commune $model): bool
    {
        return $user->can(Permission::ManageTerritories->value);
    }

    public function delete(User $user, Commune $model): bool
    {
        return $user->can(Permission::ManageTerritories->value);
    }

    public function deleteAny(User $user): bool
    {
        return $user->can(Permission::ManageTerritories->value);
    }
}
