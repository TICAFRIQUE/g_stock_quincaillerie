<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ligne_inventaires', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventaire_id')->constrained('inventaires')->cascadeOnDelete();
            $table->foreignId('produit_id')->constrained('produits')->restrictOnDelete();
            // Snapshot au comptage : ne jamais recalculer en live (reproductibilité).
            $table->unsignedInteger('quantite_theorique');
            $table->unsignedInteger('quantite_comptee');
            $table->integer('ecart'); // = quantite_comptee - quantite_theorique
            $table->timestamps();
            $table->unique(['inventaire_id', 'produit_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ligne_inventaires');
    }
};
