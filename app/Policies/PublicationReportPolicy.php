<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\PublicationReport;
use App\Models\User;

class PublicationReportPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::ManageReports->value);
    }

    public function view(User $user, PublicationReport $report): bool
    {
        return $user->can(Permission::ManageReports->value);
    }

    public function update(User $user, PublicationReport $report): bool
    {
        return $user->can(Permission::ManageReports->value);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function delete(User $user, PublicationReport $report): bool
    {
        return false;
    }
}
