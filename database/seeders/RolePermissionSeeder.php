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
        //
        // Catalogue moins 'superadmin_only' (role.gerer/utilisateur.gerer,
        // voir config/permissions.php) : un rôle avec role.gerer pourrait
        // créer d'autres rôles avec n'importe quelle permission,
        // utilisateur.gerer pourrait créer de nouveaux comptes admin — le
        // Gérant seedé par défaut ne doit donc pas les recevoir d'office,
        // même si un Superadmin reste libre de les lui accorder ensuite
        // depuis /roles.
        $gerant = Role::firstOrCreate(['name' => 'Gérant']);
        if ($gerant->wasRecentlyCreated) {
            $gerant->syncPermissions(
                collect(config('permissions.catalogue'))
                    ->diff(config('permissions.superadmin_only'))
            );
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
