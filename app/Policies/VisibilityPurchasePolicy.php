<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\User;
use App\Models\VisibilityPurchase;

class VisibilityPurchasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::ManageVisibility->value) || $user->can(Permission::ViewPayments->value);
    }

    public function view(User $user, VisibilityPurchase $purchase): bool
    {
        return $purchase->user_id === $user->id || $this->viewAny($user);
    }

    public function cancel(User $user, VisibilityPurchase $purchase): bool
    {
        return $purchase->user_id === $user->id || $user->can(Permission::ManageVisibility->value);
    }

    /**
     * Confirmation manuelle d'un paiement reçu (virement, chèque).
     */
    public function confirmPayment(User $user, VisibilityPurchase $purchase): bool
    {
        return $user->can(Permission::ManageVisibility->value);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, VisibilityPurchase $purchase): bool
    {
        return false;
    }

    public function delete(User $user, VisibilityPurchase $purchase): bool
    {
        return false;
    }
}
