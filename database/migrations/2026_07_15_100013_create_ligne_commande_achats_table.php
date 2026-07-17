<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ligne_commande_achats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commande_achat_id')->constrained('commande_achats')->cascadeOnDelete();
            $table->foreignId('produit_id')->constrained('produits')->restrictOnDelete();
            $table->unsignedInteger('quantite');
            $table->unsignedInteger('prix_achat');
            $table->timestamps();
            $table->unique(['commande_achat_id', 'produit_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ligne_commande_achats');
    }
};
