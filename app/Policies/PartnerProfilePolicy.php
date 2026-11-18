<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\PartnerProfile;
use App\Models\User;

class PartnerProfilePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::ManagePartners->value);
    }

    public function view(User $user, PartnerProfile $model): bool
    {
        return $user->can(Permission::ManagePartners->value);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::ManagePartners->value);
    }

    public function update(User $user, PartnerProfile $model): bool
    {
        return $user->can(Permission::ManagePartners->value);
    }

    /**
     * Pas de suppression si des achats y sont rattachés (historique) : désactiver plutôt.
     */
    public function delete(User $user, PartnerProfile $model): bool
    {
        return $user->can(Permission::ManagePartners->value) && ! $model->purchases()->exists();
    }
}
