<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Usage : ->middleware('role:responsable,admin')
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! $user->hasRole(...array_map(fn ($role) => Role::from($role), $roles))) {
            abort(403, 'Cet espace est réservé à un autre profil d\'utilisateur.');
        }

        return $next($request);
    }
}
