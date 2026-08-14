<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lieu où le vendeur envisage de prélever le stock, par ligne — purement
     * indicatif (un devis ne réserve jamais de stock, règle 15). Nullable :
     * null = magasin du devis (comportement par défaut, voir
     * Devis::lignesEnRuptureDeStock()). Repris comme valeur de départ
     * (modifiable) au moment de la transformation en vente.
     */
    public function up(): void
    {
        Schema::table('ligne_devis', function (Blueprint $table) {
            $table->foreignId('magasin_source_id')->nullable()->after('unite_vente_id')
                ->constrained('magasins')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ligne_devis', function (Blueprint $table) {
            $table->dropConstrainedForeignId('magasin_source_id');
        });
    }
};
