<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Territory;
use App\Models\User;

class TerritoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::ManageTerritories->value);
    }

    public function view(User $user, Territory $model): bool
    {
        return $user->can(Permission::ManageTerritories->value);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::ManageTerritories->value);
    }

    public function update(User $user, Territory $model): bool
    {
        return $user->can(Permission::ManageTerritories->value);
    }

    public function delete(User $user, Territory $model): bool
    {
        return $user->can(Permission::ManageTerritories->value);
    }

    public function deleteAny(User $user): bool
    {
        return $user->can(Permission::ManageTerritories->value);
    }
}
