<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Garde des routes de gestion de l'abonnement (formules, activation,
 * coordonnées de contact) — jamais via le système de permissions Spatie,
 * voir User::estGestionnaireAbonnement().
 */
class AssureGestionnaireAbonnement
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->estGestionnaireAbonnement(), 403);

        return $next($request);
    }
}
