<?php

namespace App\Providers;

use App\Support\Settings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // scoped : réinitialisé à chaque requête et à chaque job (workers longue durée).
        $this->app->scoped(Settings::class);
    }

    public function boot(): void
    {
        // Détecte en dev le lazy loading (N+1), les attributs inexistants, etc.
        Model::shouldBeStrict(! $this->app->isProduction());

        Route::resourceVerbs(['create' => 'creer', 'edit' => 'modifier']);

        Password::defaults(fn () => $this->app->isProduction()
            ? Password::min(10)->letters()->mixedCase()->numbers()->uncompromised()
            : Password::min(8));

    }
}
