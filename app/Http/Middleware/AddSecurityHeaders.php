<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * En-têtes qui demandent au navigateur de se protéger lui-même :
 * - X-Frame-Options : interdit d'afficher EcoCom dans le cadre d'un autre site (clickjacking : faux bouton par-dessus « Payer ») ;
 * - X-Content-Type-Options : le navigateur ne « devine » pas le type d'un fichier envoyé (une fausse image ne devient pas une page) ;
 * - Referrer-Policy : les autres sites ne voient pas l'adresse complète de la page d'où l'on vient ;
 * - Permissions-Policy : seul EcoCom peut demander la position GPS et l'appareil photo, jamais un contenu tiers.
 */
class AddSecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(self), camera=(self), microphone=()');

        return $response;
    }
}
