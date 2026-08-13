<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Encaissement d'une dette fournisseur, immuable comme ReglementClient
     * mais SANS lien avec une caisse : indépendant de la session de caisse,
     * n'impacte jamais le tiroir (décision produit — voir CLAUDE.md).
     */
    public function up(): void
    {
        Schema::create('reglement_fournisseurs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fournisseur_id')->constrained('fournisseurs')->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->unsignedInteger('montant');
            $table->timestamp('created_at')->useCurrent();
            $table->index('fournisseur_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reglement_fournisseurs');
    }
};
