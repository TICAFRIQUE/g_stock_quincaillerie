<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Une ligne = la quantité (en pièces) reçue pour une LigneCommandeAchat
     * précise, lors d'une réception donnée. Contrairement à LigneCommandeAchat,
     * aucune unité d'achat stockée ici : une réception raisonne directement
     * en pièces (unité de base) et prix par pièce (voir CLAUDE.md — s'alimente
     * sans conversion dans StockService::enregistrerMouvement()).
     *
     * magasin_id N'EST PAS une copie automatique de la destination prévue à
     * la commande (magasin_destination_id de la ligne) : c'est un choix
     * éditable à la réception, pré-rempli côté formulaire mais librement
     * modifiable — la répartition réelle peut différer du plan initial.
     *
     * prix_achat_reel : prix réellement facturé par le fournisseur pour ce
     * lot (peut différer de l'indicatif de la ligne de commande — négociation),
     * c'est CE prix qui alimente le CMP. taxe_id repris de la ligne de
     * commande (non re-choisi à la réception).
     */
    public function up(): void
    {
        Schema::create('ligne_reception_achats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reception_achat_id')->constrained('reception_achats')->restrictOnDelete();
            $table->foreignId('ligne_commande_achat_id')->constrained('ligne_commande_achats')->restrictOnDelete();
            $table->foreignId('produit_id')->constrained('produits')->restrictOnDelete();
            $table->foreignId('magasin_id')->constrained('magasins')->restrictOnDelete();
            $table->decimal('quantite_pieces', 12, 3);
            $table->unsignedInteger('prix_achat_reel');
            $table->foreignId('taxe_id')->nullable()->constrained('taxes')->restrictOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->index('reception_achat_id');
            $table->index('ligne_commande_achat_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ligne_reception_achats');
    }
};
