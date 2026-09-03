<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Réception fournisseur : constat de marchandise physiquement arrivée
     * pour une commande d'achat précise, ligne par ligne — une commande peut
     * être reçue en plusieurs fois. Contrairement au bon de livraison
     * (purement informatif), une réception mouvemente réellement le stock,
     * recalcule le CMP et pose la dette fournisseur au moment où elle est
     * enregistrée (voir ReceptionAchatService) — immuable comme un
     * mouvement de stock ou une écriture de compte (règle 2/16), jamais
     * annulable : une correction se fait via un retour fournisseur.
     */
    public function up(): void
    {
        Schema::create('reception_achats', function (Blueprint $table) {
            $table->id();
            $table->string('numero')->unique();
            $table->foreignId('commande_achat_id')->constrained('commande_achats')->restrictOnDelete();
            $table->string('motif')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->index('commande_achat_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reception_achats');
    }
};
