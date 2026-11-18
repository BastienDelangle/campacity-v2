<?php

namespace App\Providers;

use App\Models\VisibilityPurchase;
use App\Support\Settings;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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

        // Noms stables pour les relations polymorphes des paiements.
        Relation::morphMap([
            'visibility_purchase' => VisibilityPurchase::class,
        ]);

        Route::resourceVerbs(['create' => 'creer', 'edit' => 'modifier']);

        // Formulaires publics (contact, signalement) : limite par IP.
        RateLimiter::for('public-forms', fn (Request $request) => [
            Limit::perMinute(3)->by('pf-min|'.$request->ip()),
            Limit::perDay(30)->by('pf-day|'.$request->ip()),
        ]);

        Password::defaults(fn () => $this->app->isProduction()
            ? Password::min(10)->letters()->mixedCase()->numbers()->uncompromised()
            : Password::min(8));

    }
}
