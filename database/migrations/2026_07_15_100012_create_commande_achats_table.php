<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pas d'entité "Réception" séparée : à la validation, le stock est
        // directement impacté (voir ligne_commande_achats + mouvement_stocks).
        Schema::create('commande_achats', function (Blueprint $table) {
            $table->id();
            $table->string('numero')->unique();
            $table->foreignId('fournisseur_id')->constrained('fournisseurs')->restrictOnDelete();
            $table->foreignId('magasin_id')->constrained('magasins')->restrictOnDelete();
            $table->string('statut')->default('brouillon'); // brouillon | validee | annulee
            $table->date('date_commande');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('valide_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('valide_at')->nullable();
            $table->timestamps();
            $table->index('statut');
            $table->index('date_commande');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commande_achats');
    }
};
