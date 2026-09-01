<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ligne_ventes', function (Blueprint $table) {
            // Purement informatif pour l'affichage : la remise est calculée
            // et stockée exactement comme une remise "montant" classique
            // (remise_ligne_type/valeur/montant, jamais touchés). Ce champ
            // sert uniquement à savoir, au moment d'imprimer le ticket/la
            // facture, s'il faut afficher le prix personnalisé saisi à la
            // place du prix catalogue (et masquer la colonne Remise) — voir
            // ventes/ticket.blade.php et ventes/facture.blade.php.
            $table->boolean('prix_personnalise')->default(false)->after('remise_ligne_montant');
        });
    }

    public function down(): void
    {
        Schema::table('ligne_ventes', function (Blueprint $table) {
            $table->dropColumn('prix_personnalise');
        });
    }
};
