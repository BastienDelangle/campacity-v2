<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\NotificationLog;
use App\Models\User;

/**
 * Lecture et actions dédiées uniquement : pas de création/modification libre,
 * pour garantir la cohérence avec les paiements.
 */
class NotificationLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::ViewNotificationLogs->value);
    }

    public function view(User $user, NotificationLog $model): bool
    {
        return $user->can(Permission::ViewNotificationLogs->value);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, NotificationLog $model): bool
    {
        return false;
    }

    public function delete(User $user, NotificationLog $model): bool
    {
        return false;
    }
}
