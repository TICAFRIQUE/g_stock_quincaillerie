<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Aucune colonne prix/remise : le panier repris applique le prix courant
        // du produit, pas de figeage à la mise en attente.
        Schema::create('ligne_vente_en_attentes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vente_en_attente_id')->constrained('vente_en_attentes')->cascadeOnDelete();
            $table->foreignId('produit_id')->constrained('produits')->restrictOnDelete();
            $table->foreignId('unite_vente_id')->nullable()->constrained('unite_ventes')->restrictOnDelete();
            $table->unsignedInteger('quantite');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ligne_vente_en_attentes');
    }
};
