<?php

namespace Database\Seeders;

use App\Models\FormuleAbonnement;
use Illuminate\Database\Seeder;

/**
 * Formules de départ, modifiables ensuite depuis Gestion abonnement (prix
 * volontairement indicatifs — à ajuster). firstOrCreate() : relancer ce
 * seeder ne duplique jamais la liste (même précaution que MotifMouvementSeeder).
 */
class FormuleAbonnementSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['nom' => 'Essai', 'jours' => 7, 'illimite' => false, 'prix' => 0],
            ['nom' => 'Mensuelle', 'jours' => 30, 'illimite' => false, 'prix' => 15000],
            ['nom' => 'Illimité', 'jours' => null, 'illimite' => true, 'prix' => 300000],
        ] as $formule) {
            FormuleAbonnement::firstOrCreate(['nom' => $formule['nom']], [
                'jours' => $formule['jours'],
                'illimite' => $formule['illimite'],
                'prix' => $formule['prix'],
                'actif' => true,
            ]);
        }
    }
}
