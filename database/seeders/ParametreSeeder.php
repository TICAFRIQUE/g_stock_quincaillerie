<?php

namespace Database\Seeders;

use App\Models\Parametre;
use Illuminate\Database\Seeder;

class ParametreSeeder extends Seeder
{
    public function run(): void
    {
        $parametre = Parametre::query()->firstOrCreate(['id' => 1], [
            'nom' => "Plaisir d'Offrir, Joie de Recevoir",
        ]);

        $logoSource = public_path('images/logo.jpeg');

        if (! $parametre->getFirstMedia('logo') && file_exists($logoSource)) {
            $parametre->addMedia($logoSource)->preservingOriginal()->toMediaCollection('logo');
        }

        Parametre::invaliderCache();
    }
}
