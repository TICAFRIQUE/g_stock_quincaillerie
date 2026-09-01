<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bon de livraison : constat logistique de remise physique d'une partie
     * (ou de la totalité) des articles d'une vente au client — une vente peut
     * être livrée en plusieurs fois. Ne mouvemente jamais le stock (déjà fait
     * à la vente, règle 3) ni la caisse/le compte client. Annulable (soft
     * delete + motif), contrairement à un retour/mouvement strictement
     * immuable, car il n'a aucun impact stock/argent à réconcilier.
     */
    public function up(): void
    {
        Schema::create('bon_livraisons', function (Blueprint $table) {
            $table->id();
            $table->string('numero')->unique();
            $table->foreignId('vente_id')->constrained('ventes')->restrictOnDelete();
            $table->string('motif')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->text('motif_annulation')->nullable();
            $table->foreignId('annulee_par')->nullable()->constrained('users')->restrictOnDelete();
            $table->softDeletes();
            $table->index('vente_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bon_livraisons');
    }
};
