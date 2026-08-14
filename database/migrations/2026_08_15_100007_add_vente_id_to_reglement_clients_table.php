<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rattachement optionnel d'un règlement à une vente précise (bouton
     * "Régler" déclenché depuis le détail d'une facture ou depuis une ligne
     * de l'historique du compte client) : permet de calculer un reste dû par
     * vente — même principe que reglement_fournisseurs.commande_achat_id. Un
     * règlement saisi sans vente (bouton général) reste imputé uniquement au
     * solde global du client.
     */
    public function up(): void
    {
        Schema::table('reglement_clients', function (Blueprint $table) {
            $table->foreignId('vente_id')->nullable()->after('client_id')
                ->constrained('ventes')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('reglement_clients', function (Blueprint $table) {
            $table->dropConstrainedForeignId('vente_id');
        });
    }
};
