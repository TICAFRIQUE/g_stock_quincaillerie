<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;

/**
 * Historique de connexions (règle de traçabilité) : login/logout, date,
 * utilisateur, IP — et désormais les tentatives échouées (audit sécurité :
 * une attaque par force brute sur le code PIN ne laissait auparavant aucune
 * trace exploitable).
 */
class LogAuthentication
{
    public function handleLogin(Login $event): void
    {
        activity('auth')
            ->causedBy($event->user)
            ->performedOn($event->user)
            ->withProperties(['ip' => request()->ip()])
            ->log('Connexion');
    }

    /**
     * Ne jamais logger $event->credentials['password'] (le code PIN en
     * clair) — uniquement le nom d'utilisateur tenté et l'IP.
     */
    public function handleFailed(Failed $event): void
    {
        activity('auth')
            ->withProperties([
                'ip' => request()->ip(),
                'username_tente' => $event->credentials['username'] ?? null,
            ])
            ->log('Échec de connexion');
    }

    public function handleLogout(Logout $event): void
    {
        if (! $event->user) {
            return;
        }

        activity('auth')
            ->causedBy($event->user)
            ->performedOn($event->user)
            ->withProperties(['ip' => request()->ip()])
            ->log('Déconnexion');
    }
}
