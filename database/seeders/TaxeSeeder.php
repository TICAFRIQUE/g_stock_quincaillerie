<?php

namespace Database\Seeders;

use App\Models\Taxe;
use Illuminate\Database\Seeder;

class TaxeSeeder extends Seeder
{
    public function run(): void
    {
        Taxe::firstOrCreate(['nom' => 'Exonéré'], ['taux' => 0, 'actif' => true]);
        Taxe::firstOrCreate(['nom' => 'TVA 18%'], ['taux' => 18, 'actif' => true]);
    }
}
