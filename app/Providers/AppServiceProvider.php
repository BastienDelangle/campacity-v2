<?php

namespace App\Providers;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Capacités jamais court-circuitées par le rôle administrateur.
     *
     * @var list<string>
     */
    private const SELF_PROTECTED_ABILITIES = ['assignRoles'];

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Détecte en dev le lazy loading (N+1), les attributs inexistants, etc.
        Model::shouldBeStrict(! $this->app->isProduction());

        Route::resourceVerbs(['create' => 'creer', 'edit' => 'modifier']);

        Password::defaults(fn () => $this->app->isProduction()
            ? Password::min(10)->letters()->mixedCase()->numbers()->uncompromised()
            : Password::min(8));

        // L'administrateur dispose de tous les droits de gestion, sauf les
        // garde-fous qui le protègent de lui-même (ex. modifier ses propres rôles).
        Gate::before(function (User $user, string $ability): ?bool {
            if (in_array($ability, self::SELF_PROTECTED_ABILITIES, true)) {
                return null;
            }

            return $user->hasRole(Role::Admin->value) ? true : null;
        });
    }
}
