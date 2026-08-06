<?php

namespace Database\Seeders;

use App\Models\Unite;
use Illuminate\Database\Seeder;

class UniteSeeder extends Seeder
{
    public function run(): void
    {
        // Normalise la valeur héritée du backfill de migration (toujours
        // "pièce" avant l'introduction de ce référentiel).
        Unite::where('nom', 'pièce')->update(['nom' => 'Pièce']);

        // Abréviation pertinente pour une unité de mesure (Litre -> L),
        // laissée vide pour un conditionnement (Carton, Bidon…).
        foreach ([
            'Pièce' => 'pc',
            'Litre' => 'L',
            'Mètre' => 'm',
            'Kilogramme' => 'kg',
            'Carton' => null,
            'Bidon' => null,
            'Sac' => null,
            'Rouleau' => null,
            'Boîte' => null,
            'Lot' => null,
        ] as $nom => $abbreviation) {
            $unite = Unite::firstOrCreate(['nom' => $nom], ['actif' => true]);
            if ($unite->abbreviation === null && $abbreviation !== null) {
                $unite->update(['abbreviation' => $abbreviation]);
            }
        }
    }
}
