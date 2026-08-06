<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Aucune colonne prix/coût : les montants du devis sont indicatifs,
        // calculés à partir du prix courant du catalogue au moment de
        // l'affichage — comme une ligne de vente en attente (voir CLAUDE.md).
        Schema::create('ligne_devis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('devis_id')->constrained('devis')->cascadeOnDelete();
            $table->foreignId('produit_id')->constrained('produits')->restrictOnDelete();
            $table->foreignId('unite_vente_id')->nullable()->constrained('unite_ventes')->restrictOnDelete();
            $table->unsignedInteger('quantite');
            $table->string('remise_type')->nullable(); // montant | pourcentage
            $table->unsignedInteger('remise_valeur')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ligne_devis');
    }
};
