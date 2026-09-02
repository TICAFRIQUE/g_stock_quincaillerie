<?php

namespace Database\Seeders;

use App\Models\Devise;
use App\Models\Parametre;
use Illuminate\Database\Seeder;

/**
 * Référentiel de départ, modifiable/complétable ensuite en Administration.
 * firstOrCreate() : relancer ce seeder ne duplique jamais la liste (même
 * précaution que MotifMouvementSeeder/FormuleAbonnementSeeder). FCFA est la
 * devise active par défaut — jamais écrasée si Paramètres a déjà une
 * devise choisie (ex. réinstallation, relance du seeder).
 */
class DeviseSeeder extends Seeder
{
    public function run(): void
    {
        $fcfa = Devise::firstOrCreate(['nom' => 'Franc CFA'], ['abreviation' => 'FCFA', 'actif' => true]);
        Devise::firstOrCreate(['nom' => 'Euro'], ['abreviation' => '€', 'actif' => true]);
        Devise::firstOrCreate(['nom' => 'Dollar américain'], ['abreviation' => '$', 'actif' => true]);

        $parametre = Parametre::actuel();
        if (! $parametre->devise_id) {
            $parametre->update(['devise_id' => $fcfa->id]);
        }

        // DatabaseSeeder utilise WithoutModelEvents : les hooks saved() de
        // Devise/Parametre qui invalident normalement ces caches ne se
        // déclenchent pas ici (même contrainte que ParametreSeeder).
        Devise::invaliderCache();
        Parametre::invaliderCache();
    }
}
