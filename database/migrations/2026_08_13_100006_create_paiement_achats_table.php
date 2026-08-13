<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mirroring paiements (ventes) : paiement(s) encaissés au moment de la
     * validation d'une commande d'achat. Le reste (total TTC - paiements)
     * devient une dette fournisseur (voir CompteFournisseurService).
     */
    public function up(): void
    {
        Schema::create('paiement_achats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commande_achat_id')->constrained('commande_achats')->restrictOnDelete();
            $table->foreignId('moyen_paiement_id')->constrained('moyen_paiements')->restrictOnDelete();
            $table->unsignedInteger('montant');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paiement_achats');
    }
};
