<?php

namespace App\Providers;

use App\Listeners\LogAuthentication;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Le Superadmin contourne toutes les permissions ; il n'en reçoit aucune.
        Gate::before(fn (User $user) => $user->hasRole('Superadmin') ? true : null);

        // Laravel génère une pagination stylée Tailwind par défaut ; l'app utilise
        // Bootstrap, pas Tailwind, d'où un rendu cassé sans cette ligne.
        Paginator::useBootstrapFive();

        Event::listen(Login::class, [LogAuthentication::class, 'handleLogin']);
        Event::listen(Logout::class, [LogAuthentication::class, 'handleLogout']);
    }
}
