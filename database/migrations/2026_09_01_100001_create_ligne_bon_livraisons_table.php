<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Une ligne = la quantité (en pièces) livrée pour une LigneVente précise,
     * lors d'un bon de livraison donné. magasin_id reprend le
     * magasin_source_id de la ligne de vente au moment de la livraison (pur
     * historique — aucun mouvement de stock associé, le stock a déjà bougé à
     * la vente, voir CLAUDE.md règle 3). Aucune dimension financière : un
     * bon de livraison n'a ni prix ni coût.
     */
    public function up(): void
    {
        Schema::create('ligne_bon_livraisons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bon_livraison_id')->constrained('bon_livraisons')->restrictOnDelete();
            $table->foreignId('ligne_vente_id')->constrained('ligne_ventes')->restrictOnDelete();
            $table->foreignId('produit_id')->constrained('produits')->restrictOnDelete();
            $table->foreignId('magasin_id')->constrained('magasins')->restrictOnDelete();
            $table->unsignedInteger('quantite_pieces');
            $table->timestamp('created_at')->useCurrent();
            $table->index('bon_livraison_id');
            $table->index('ligne_vente_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ligne_bon_livraisons');
    }
};
