<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rattachement optionnel d'un règlement à une commande d'achat précise
     * (bouton "Régler" déclenché depuis le détail d'un achat ou depuis une
     * ligne de l'historique du compte fournisseur) : permet de calculer un
     * reste dû par commande. Un règlement saisi sans commande (bouton
     * général "Régler" de la fiche fournisseur) reste imputé uniquement au
     * solde global du fournisseur.
     */
    public function up(): void
    {
        Schema::table('reglement_fournisseurs', function (Blueprint $table) {
            $table->foreignId('commande_achat_id')->nullable()->after('fournisseur_id')
                ->constrained('commande_achats')->restrictOnDelete();
            $table->index('commande_achat_id');
        });
    }

    public function down(): void
    {
        Schema::table('reglement_fournisseurs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('commande_achat_id');
        });
    }
};
