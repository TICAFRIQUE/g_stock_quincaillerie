<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Retour arrière : la destination par ligne devait rester purement
     * informative (afficher le stock par lieu au vendeur), jamais un choix
     * à enregistrer — un sélecteur cliquable créait une confusion sur le
     * rôle du champ. L'affichage informatif reste possible sans stocker
     * quoi que ce soit (calculé à la volée, voir DevisController).
     */
    public function up(): void
    {
        Schema::table('ligne_devis', function (Blueprint $table) {
            $table->dropConstrainedForeignId('magasin_source_id');
        });
    }

    public function down(): void
    {
        Schema::table('ligne_devis', function (Blueprint $table) {
            $table->foreignId('magasin_source_id')->nullable()->after('unite_vente_id')
                ->constrained('magasins')->restrictOnDelete();
        });
    }
};
