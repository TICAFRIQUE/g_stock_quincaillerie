<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Même champ que ligne_ventes, pour que le choix de source survive à une
     * mise en attente/reprise du panier (voir VenteController::reprendre()).
     */
    public function up(): void
    {
        Schema::table('ligne_vente_en_attentes', function (Blueprint $table) {
            $table->foreignId('magasin_source_id')->nullable()->after('unite_vente_id')
                ->constrained('magasins')->restrictOnDelete();
        });

        DB::statement('
            UPDATE ligne_vente_en_attentes
            INNER JOIN vente_en_attentes ON vente_en_attentes.id = ligne_vente_en_attentes.vente_en_attente_id
            SET ligne_vente_en_attentes.magasin_source_id = vente_en_attentes.magasin_id
        ');
    }

    public function down(): void
    {
        Schema::table('ligne_vente_en_attentes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('magasin_source_id');
        });
    }
};
