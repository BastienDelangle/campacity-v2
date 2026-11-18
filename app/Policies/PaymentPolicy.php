<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Payment;
use App\Models\User;

/**
 * Les paiements sont en lecture seule dans l'administration : leur statut ne
 * change que via PaymentManager (confirmation manuelle ou notification du prestataire).
 */
class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::ViewPayments->value);
    }

    public function view(User $user, Payment $payment): bool
    {
        return $user->can(Permission::ViewPayments->value);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Payment $payment): bool
    {
        return false;
    }

    public function delete(User $user, Payment $payment): bool
    {
        return false;
    }
}
