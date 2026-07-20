<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * La racine redirige vers le tableau de bord, qui exige une session
     * authentifiée : un visiteur non connecté doit atterrir sur la page de
     * connexion.
     */
    public function test_un_visiteur_non_connecte_est_redirige_vers_la_connexion(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/dashboard');
        $this->get('/dashboard')->assertRedirect('/login');
    }
}
