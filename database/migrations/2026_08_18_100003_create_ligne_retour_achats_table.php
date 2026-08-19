<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * magasin_id reprend le magasin_destination_id de la ligne d'achat au
     * moment du retour : c'est de là que le stock est repris.
     */
    public function up(): void
    {
        Schema::create('ligne_retour_achats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('retour_achat_id')->constrained('retour_achats')->restrictOnDelete();
            $table->foreignId('ligne_commande_achat_id')->constrained('ligne_commande_achats')->restrictOnDelete();
            $table->foreignId('produit_id')->constrained('produits')->restrictOnDelete();
            $table->foreignId('magasin_id')->constrained('magasins')->restrictOnDelete();
            $table->unsignedInteger('quantite_pieces');
            $table->unsignedInteger('montant');
            $table->timestamp('created_at')->useCurrent();
            $table->index('retour_achat_id');
            $table->index('ligne_commande_achat_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ligne_retour_achats');
    }
};
