<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Une ligne = la quantité (en pièces) retournée pour une LigneVente
     * précise. magasin_id reprend le magasin_source_id de la ligne de vente
     * au moment du retour : c'est là que le stock est restitué. cout_applique
     * copie celui de la ligne de vente (base de coût figée, pas de recalcul
     * de CMP sur un retour — voir CLAUDE.md).
     */
    public function up(): void
    {
        Schema::create('ligne_retour_ventes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('retour_vente_id')->constrained('retour_ventes')->restrictOnDelete();
            $table->foreignId('ligne_vente_id')->constrained('ligne_ventes')->restrictOnDelete();
            $table->foreignId('produit_id')->constrained('produits')->restrictOnDelete();
            $table->foreignId('magasin_id')->constrained('magasins')->restrictOnDelete();
            $table->unsignedInteger('quantite_pieces');
            $table->unsignedInteger('montant');
            $table->unsignedInteger('cout_applique');
            $table->timestamp('created_at')->useCurrent();
            $table->index('retour_vente_id');
            $table->index('ligne_vente_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ligne_retour_ventes');
    }
};
