<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unite_ventes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produit_id')->constrained('produits')->restrictOnDelete();
            $table->string('libelle');
            $table->unsignedInteger('facteur');
            $table->unsignedInteger('prix');
            $table->boolean('actif')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['produit_id', 'libelle']);
        });

        // Une unité de vente représente toujours un lot (facteur >= 2) ; la pièce
        // elle-même (facteur = 1) est le prix_piece du produit, pas une unité de vente.
        DB::statement('ALTER TABLE unite_ventes ADD CONSTRAINT chk_unite_ventes_facteur CHECK (facteur >= 2)');
    }

    public function down(): void
    {
        Schema::dropIfExists('unite_ventes');
    }
};
