<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        collect(config('permissions.catalogue'))->each(
            fn (string $permission) => Permission::firstOrCreate(['name' => $permission])
        );

        $superadmin = Role::firstOrCreate(['name' => 'Superadmin']);
        // Aucune permission assignée : le bypass se fait via Gate::before
        // (voir AppServiceProvider::boot()).

        $gerant = Role::firstOrCreate(['name' => 'Gérant']);
        $gerant->syncPermissions(config('permissions.catalogue'));

        $caissier = Role::firstOrCreate(['name' => 'Caissier']);
        $caissier->syncPermissions([
            'produit.voir',
            'stock.voir',
            'vente.voir',
            'vente.creer',
            'ventenattente.gerer',
            'caisse.ouvrir',
            'caisse.cloturer',
        ]);
    }
}
