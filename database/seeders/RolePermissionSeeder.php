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

        Role::firstOrCreate(['name' => 'Superadmin']);
        // Aucune permission assignée : le bypass se fait via Gate::before
        // (voir AppServiceProvider::boot()).

        // syncPermissions() uniquement à la création : Gérant/Caissier sont
        // des rôles par défaut mais restent modifiables ensuite (comme un
        // rôle créé à la volée) — sans ce garde-fou, relancer ce seeder après
        // l'ajout d'une permission (config/permissions.php) écrasait
        // silencieusement toute personnalisation faite depuis /roles.
        $gerant = Role::firstOrCreate(['name' => 'Gérant']);
        if ($gerant->wasRecentlyCreated) {
            $gerant->syncPermissions(config('permissions.catalogue'));
        }

        $caissier = Role::firstOrCreate(['name' => 'Caissier']);
        if ($caissier->wasRecentlyCreated) {
            $caissier->syncPermissions([
                'produit.voir',
                'stock.voir',
                'vente.voir',
                'vente.creer',
                'vente.credit',
                'vente.signaler',
                'vente.retour',
                'vente.livrer',
                'ventenattente.gerer',
                'client.voir',
                'client.gerer',
                'client.reglement',
                'devis.voir',
                'devis.gerer',
                'devis.transformer',
                'caisse.ouvrir',
                'caisse.cloturer',
                'caisse.mouvement',
            ]);
        }
    }
}
