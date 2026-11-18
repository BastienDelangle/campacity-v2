<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Publication;
use App\Models\User;

class PublicationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::ModeratePublications->value) || $user->can(Permission::ManagePublications->value);
    }

    /**
     * Consultation : publique si visible, sinon réservée aux gestionnaires
     * de la publication (prévisualisation) et aux modérateurs.
     */
    public function view(?User $user, Publication $publication): bool
    {
        if ($publication->isPubliclyVisible()) {
            return true;
        }

        return $user !== null && ($this->manages($user, $publication) || $this->viewAny($user));
    }

    public function create(User $user): bool
    {
        return $user->hasVerifiedEmail() && ! $user->isAnonymized();
    }

    /**
     * Modification du contenu : l'auteur, ou un responsable de l'organisation
     * associée. Une publication archivée ou expirée doit d'abord être renouvelée.
     */
    public function update(User $user, Publication $publication): bool
    {
        if ($user->can(Permission::ManagePublications->value)) {
            return true;
        }

        return $this->manages($user, $publication) && $publication->status->isEditable();
    }

    public function manage(User $user, Publication $publication): bool
    {
        return $this->manages($user, $publication) || $user->can(Permission::ManagePublications->value);
    }

    public function delete(User $user, Publication $publication): bool
    {
        return $this->manage($user, $publication);
    }

    public function restore(User $user, Publication $publication): bool
    {
        return $user->can(Permission::ManagePublications->value);
    }

    public function forceDelete(User $user, Publication $publication): bool
    {
        return $user->can(Permission::ManagePublications->value);
    }

    public function moderate(User $user, Publication $publication): bool
    {
        return $user->can(Permission::ModeratePublications->value);
    }

    private function manages(User $user, Publication $publication): bool
    {
        if ($publication->user_id === $user->id) {
            return true;
        }

        return $publication->organization_id !== null
            && $user->organizations()->whereKey($publication->organization_id)->exists();
    }
}
