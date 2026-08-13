<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lieu de prélèvement du stock, par ligne (un produit peut être vendu
     * depuis un magasin ou un dépôt différent du magasin de la vente). Nullable
     * en base (pas de doctrine/dbal pour un ->change() vers NOT NULL, voir la
     * migration équivalente sur ligne_commande_achats) mais toujours résolue
     * par VenteService::vendre() — jamais nulle après écriture. Les lignes
     * existantes sont backfillées au magasin de leur vente.
     */
    public function up(): void
    {
        Schema::table('ligne_ventes', function (Blueprint $table) {
            $table->foreignId('magasin_source_id')->nullable()->after('unite_vente_id')
                ->constrained('magasins')->restrictOnDelete();
        });

        DB::statement('
            UPDATE ligne_ventes
            INNER JOIN ventes ON ventes.id = ligne_ventes.vente_id
            SET ligne_ventes.magasin_source_id = ventes.magasin_id
        ');
    }

    public function down(): void
    {
        Schema::table('ligne_ventes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('magasin_source_id');
        });
    }
};
