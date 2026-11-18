<?php

namespace App\Providers;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Détecte en dev le lazy loading (N+1), les attributs inexistants, etc.
        Model::shouldBeStrict(! $this->app->isProduction());

        Password::defaults(fn () => $this->app->isProduction()
            ? Password::min(10)->letters()->mixedCase()->numbers()->uncompromised()
            : Password::min(8));

        // L'administrateur dispose de tous les droits de gestion.
        Gate::before(fn (User $user) => $user->hasRole(Role::Admin->value) ? true : null);
    }
}
