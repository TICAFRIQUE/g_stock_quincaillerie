<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Code identifiant, comme le SKU produit ou le numéro de vente/commande —
     * saisi ou généré automatiquement (voir ClientController/FournisseurController).
     * Ces deux tables ont déjà des lignes en production : backfill déterministe
     * sur l'id (pas de collision possible) dans la même migration, avant que
     * l'application ne s'appuie sur le champ.
     */
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('code')->nullable()->unique()->after('nom');
        });

        Schema::table('fournisseurs', function (Blueprint $table) {
            $table->string('code')->nullable()->unique()->after('nom');
        });

        DB::statement("UPDATE clients SET code = CONCAT('CLI-', LPAD(id, 6, '0')) WHERE code IS NULL");
        DB::statement("UPDATE fournisseurs SET code = CONCAT('FRN-', LPAD(id, 6, '0')) WHERE code IS NULL");
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('code');
        });

        Schema::table('fournisseurs', function (Blueprint $table) {
            $table->dropColumn('code');
        });
    }
};
