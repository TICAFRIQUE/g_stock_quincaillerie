<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ligne_ventes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vente_id')->constrained('ventes')->restrictOnDelete();
            $table->foreignId('produit_id')->constrained('produits')->restrictOnDelete();
            // null = vendu à la pièce (prix_piece du produit)
            $table->foreignId('unite_vente_id')->nullable()->constrained('unite_ventes')->restrictOnDelete();
            $table->unsignedInteger('quantite');
            $table->unsignedInteger('quantite_pieces'); // snapshot résolu = quantite x facteur
            $table->unsignedInteger('prix_unitaire_applique'); // snapshot du prix au moment de la vente
            $table->unsignedInteger('cout_applique'); // snapshot du CMP au moment de la vente (marge)
            $table->unsignedInteger('sous_total_ligne');
            $table->string('remise_ligne_type')->nullable(); // montant | pourcentage
            $table->unsignedInteger('remise_ligne_valeur')->nullable();
            $table->unsignedInteger('remise_ligne_montant')->default(0);
            $table->unsignedInteger('total_ligne');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ligne_ventes');
    }
};
