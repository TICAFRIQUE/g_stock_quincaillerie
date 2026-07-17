<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Compteur matérialisé par (produit x magasin), tenu à jour de façon
        // transactionnelle par la couche service à chaque mouvement_stocks inséré.
        // mouvement_stocks reste le registre immuable qui fait foi.
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produit_id')->constrained('produits')->restrictOnDelete();
            $table->foreignId('magasin_id')->constrained('magasins')->restrictOnDelete();
            $table->integer('quantite')->default(0);
            $table->unsignedInteger('cout_moyen_pondere')->default(0);
            $table->timestamps();
            $table->unique(['produit_id', 'magasin_id']);
            $table->index(['magasin_id', 'quantite']);
        });

        DB::statement('ALTER TABLE stocks ADD CONSTRAINT chk_stocks_quantite CHECK (quantite >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('stocks');
    }
};
