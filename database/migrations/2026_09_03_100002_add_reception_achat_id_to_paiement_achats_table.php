<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * commande_achat_id reste toujours renseigné (dénormalisé), même pour un
     * paiement saisi à une réception : CommandeAchat::paiements()/
     * montantRegle() continuent ainsi de fonctionner sans aucune
     * modification, pour les commandes historiques (paiement direct à la
     * validation, reception_achat_id null) comme pour les nouvelles
     * (paiement à la réception, reception_achat_id renseigné).
     */
    public function up(): void
    {
        Schema::table('paiement_achats', function (Blueprint $table) {
            $table->foreignId('reception_achat_id')->nullable()->after('commande_achat_id')
                ->constrained('reception_achats')->restrictOnDelete();
            $table->index('reception_achat_id');
        });
    }

    public function down(): void
    {
        Schema::table('paiement_achats', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reception_achat_id');
        });
    }
};
