<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Registre immuable : aucun UPDATE/DELETE applicatif, pas de colonne updated_at.
        // Toute correction se fait par un mouvement inverse (règle non négociable).
        Schema::create('mouvement_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produit_id')->constrained('produits')->restrictOnDelete();
            $table->foreignId('magasin_id')->constrained('magasins')->restrictOnDelete();
            $table->string('type'); // reception | vente | casse | transfert | ajustement
            $table->integer('quantite'); // signé : + entrée / - sortie
            $table->nullableMorphs('reference'); // -> CommandeAchat, Vente, Transfert, LigneInventaire
            $table->string('motif')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['produit_id', 'magasin_id']);
            $table->index('type');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mouvement_stocks');
    }
};
