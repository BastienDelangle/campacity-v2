<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::ManageTaxonomy->value);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::ManageTaxonomy->value);
    }

    public function update(User $user, Category $model): bool
    {
        return $user->can(Permission::ManageTaxonomy->value);
    }

    /**
     * Suppression impossible si des publications y sont rattachées : désactiver plutôt.
     */
    public function delete(User $user, Category $model): bool
    {
        return $user->can(Permission::ManageTaxonomy->value) && ! $model->publications()->withTrashed()->exists()
            && ! $model->children()->exists();
    }
}
