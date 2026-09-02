<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Taxe optionnelle par ligne de devis, même référentiel que côté vente
     * (voir migration ..._add_taxe_id_to_ligne_ventes_table) — contrairement
     * au prix, jamais indicatif : c'est un choix explicite du vendeur, repris
     * tel quel à la transformation en vente (voir DevisService::transformer()).
     */
    public function up(): void
    {
        Schema::table('ligne_devis', function (Blueprint $table) {
            $table->foreignId('taxe_id')->nullable()->after('unite_vente_id')
                ->constrained('taxes')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ligne_devis', function (Blueprint $table) {
            $table->dropConstrainedForeignId('taxe_id');
        });
    }
};
