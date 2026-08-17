<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

/**
 * Création du premier compte superadmin en production : DatabaseSeeder ne
 * crée un utilisateur de test que sous APP_ENV=local (voir DatabaseSeeder),
 * donc aucun compte n'existe après un déploiement client tant que cette
 * commande n'a pas été lancée.
 */
#[Signature('app:creer-superadmin')]
#[Description("Crée le compte superadmin initial d'une installation")]
class CreerSuperadmin extends Command
{
    public function handle(): int
    {
        $nom = $this->ask('Nom complet');
        $username = $this->ask("Nom d'utilisateur (identifiant de connexion)");
        $email = $this->ask('Email');
        $code = $this->secret('Code de connexion (4 chiffres ou plus)');
        $confirmation = $this->secret('Confirmer le code');

        $validateur = Validator::make(
            compact('nom', 'username', 'email', 'code'),
            [
                'nom' => ['required', 'string', 'max:255'],
                'username' => ['required', 'string', 'max:255', 'unique:users,username'],
                'email' => ['required', 'email', 'max:255'],
                'code' => ['required', 'string', 'min:4'],
            ]
        );

        if ($validateur->fails()) {
            foreach ($validateur->errors()->all() as $erreur) {
                $this->error($erreur);
            }

            return self::FAILURE;
        }

        if ($code !== $confirmation) {
            $this->error('Les deux codes ne correspondent pas.');

            return self::FAILURE;
        }

        $superadmin = User::create([
            'name' => $nom,
            'username' => $username,
            'email' => $email,
            'password' => $code,
            'actif' => true,
        ]);

        $superadmin->assignRole(Role::firstOrCreate(['name' => 'Superadmin']));

        $this->info("Compte superadmin « {$username} » créé.");

        return self::SUCCESS;
    }
}
