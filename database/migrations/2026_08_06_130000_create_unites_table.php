<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Référentiel central des unités (mesure ou conditionnement), géré en
     * administration — réutilisé à la fois pour l'unité de base d'un produit
     * (Litre, Mètre, Kg, Pièce…) et pour le nom des unités de vente
     * (Carton, Bidon, Sac, Rouleau…), voir CLAUDE.md.
     */
    public function up(): void
    {
        Schema::create('unites', function (Blueprint $table) {
            $table->id();
            $table->string('nom')->unique();
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unites');
    }
};
