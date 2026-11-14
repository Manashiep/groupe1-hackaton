<?php

namespace App\Providers;

use Filament\Auth\Http\Responses\Contracts\LogoutResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Déconnexion depuis l'espace mairie : retour direct à la page de connexion de l'application.
        // (Sans ce détour évité, Laravel retenait « /admin » comme page à rouvrir après la prochaine connexion.)
        $this->app->bind(LogoutResponse::class, fn () => new class implements LogoutResponse
        {
            public function toResponse($request): RedirectResponse
            {
                return redirect()->route('login');
            }
        });
    }

    public function boot(): void
    {
        Carbon::setLocale(config('app.locale'));
    }
}
