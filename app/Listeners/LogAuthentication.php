<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;

/**
 * Historique de connexions (règle de traçabilité) : login/logout, date,
 * utilisateur, IP.
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
