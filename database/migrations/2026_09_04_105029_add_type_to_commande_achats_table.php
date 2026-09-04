<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Comment le document a été créé (voir CommandeAchatController::store()) :
        // "commande" = brouillon classique, réceptionné plus tard, une ou
        // plusieurs fois ; "achat_direct" = créé, validé et réceptionné en une
        // seule transaction (achat comptant déjà effectué chez le
        // fournisseur). Toutes les commandes existantes avant cette colonne
        // passent en "commande" par défaut (valeur la plus courante,
        // impossible à reconstituer avec certitude a posteriori).
        Schema::table('commande_achats', function (Blueprint $table) {
            $table->string('type', 20)->default('commande')->after('statut'); // commande | achat_direct
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('commande_achats', function (Blueprint $table) {
            $table->dropIndex(['type']);
            $table->dropColumn('type');
        });
    }
};
