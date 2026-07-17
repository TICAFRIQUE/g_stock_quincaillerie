<?php

namespace Database\Seeders;

use App\Models\MoyenPaiement;
use Illuminate\Database\Seeder;

class MoyenPaiementSeeder extends Seeder
{
    public function run(): void
    {
        MoyenPaiement::firstOrCreate(['nom' => 'Espèces'], ['actif' => true, 'est_espece' => true]);
        MoyenPaiement::firstOrCreate(['nom' => 'Mobile Money'], ['actif' => false]);
        MoyenPaiement::firstOrCreate(['nom' => 'Carte bancaire'], ['actif' => false]);
        MoyenPaiement::firstOrCreate(['nom' => 'Virement'], ['actif' => false]);
    }
}
