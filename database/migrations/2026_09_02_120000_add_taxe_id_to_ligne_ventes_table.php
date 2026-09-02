<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Taxe optionnelle par ligne de vente, même référentiel et même principe
     * que côté achat (prix_unitaire_applique/total_ligne = HT, TTC dérivé) —
     * voir LigneVente::montantTtc().
     */
    public function up(): void
    {
        Schema::table('ligne_ventes', function (Blueprint $table) {
            $table->foreignId('taxe_id')->nullable()->after('unite_vente_id')
                ->constrained('taxes')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ligne_ventes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('taxe_id');
        });
    }
};
