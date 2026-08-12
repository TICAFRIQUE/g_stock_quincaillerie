<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            MoyenPaiementSeeder::class,
            ParametreSeeder::class,
            UniteSeeder::class,
        ]);

        if (app()->environment('local')) {
            $superadmin = User::firstOrCreate(
                ['email' => 'alexkouamelan96@gmail.com'],
                [
                    'name' => 'Superadmin',
                    'password' => env('SUPERADMIN_PASSWORD', 'password'),
                    'actif' => true,
                ]
            );
            $superadmin->assignRole('Superadmin');

            // Référentiel + données transactionnelles de démonstration (au moins
            // 20 enregistrements par module transactionnel), pour tester avec du
            // volume réaliste plutôt que des listes vides.
            $this->call([
                // DemoCatalogueSeeder::class,
                // DemoTransactionsSeeder::class,
                QuincaillerieTestSeeder::class,
            ]);
        }
    }
}
