<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Retour fournisseur, symétrique de retour_ventes : toujours lié à une
     * commande d'achat validée, indépendant de la caisse (comme
     * ReglementFournisseur — voir CLAUDE.md).
     */
    public function up(): void
    {
        Schema::create('retour_achats', function (Blueprint $table) {
            $table->id();
            $table->string('numero')->unique();
            $table->foreignId('commande_achat_id')->constrained('commande_achats')->restrictOnDelete();
            $table->foreignId('fournisseur_id')->constrained('fournisseurs')->restrictOnDelete();
            $table->string('motif')->nullable();
            $table->unsignedInteger('montant_total');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->index('commande_achat_id');
            $table->index('fournisseur_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retour_achats');
    }
};
