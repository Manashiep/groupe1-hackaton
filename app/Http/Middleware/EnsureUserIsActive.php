<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Déconnecte immédiatement un compte désactivé par la mairie.
 * Sans ce contrôle, la désactivation ne bloquait que la prochaine connexion :
 * une session ouverte (ou le cookie « se souvenir de moi ») restait valable.
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->is_active) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['email' => 'Votre compte a été désactivé par la mairie.']);
        }

        return $next($request);
    }
}
