<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produits', function (Blueprint $table) {
            $table->id();
            $table->string('sku')->unique();
            $table->string('nom');
            $table->string('libelle_distinctif')->nullable();
            $table->string('code_barre')->nullable()->unique();
            $table->foreignId('categorie_id')->constrained('categories')->restrictOnDelete();
            $table->unsignedInteger('prix_piece');
            $table->unsignedInteger('seuil_alerte')->default(0);
            $table->boolean('actif')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->index('actif');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produits');
    }
};
