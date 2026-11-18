<?php

namespace App\Actions\Users;

use App\Enums\OrganizationRole;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Suppression de compte par anonymisation.
 *
 * Les données personnelles sont effacées, mais la ligne est conservée pour
 * préserver les historiques soumis à obligation de conservation (adhésions,
 * paiements, journal de modération). Le compte devient inutilisable.
 */
class AnonymizeUser
{
    public function handle(User $user): void
    {
        $this->ensureNoOrphanOrganization($user);

        DB::transaction(function () use ($user) {
            foreach ($user->organizations as $organization) {
                $isSoleMember = $organization->members()->count() === 1;
                $organization->members()->detach($user->id);

                if ($isSoleMember) {
                    $organization->delete();
                }
            }

            $user->syncRoles([]);

            $user->forceFill([
                'name' => 'Utilisateur supprimé',
                'email' => "anonyme-{$user->id}-".Str::lower(Str::random(8)).'@anonymise.invalid',
                'phone' => null,
                'website' => null,
                'show_email' => false,
                'show_phone' => false,
                'show_website' => false,
                'password' => Str::random(64),
                'remember_token' => null,
                'anonymized_at' => now(),
            ])->save();

            if (config('session.driver') === 'database') {
                DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
            }
        });
    }

    /**
     * Un propriétaire unique d'une organisation partagée doit d'abord
     * transmettre la propriété, pour ne pas laisser l'organisation sans gestionnaire.
     */
    private function ensureNoOrphanOrganization(User $user): void
    {
        $blocking = $user->organizations()
            ->wherePivot('role', OrganizationRole::Owner->value)
            ->get()
            ->filter(fn ($organization) => $organization->owners()->count() === 1 && $organization->members()->count() > 1);

        if ($blocking->isNotEmpty()) {
            throw ValidationException::withMessages([
                'account' => 'Vous êtes le seul propriétaire de : '.$blocking->pluck('name')->join(', ')
                    .'. Désignez un autre propriétaire avant de supprimer votre compte.',
            ]);
        }
    }
}
