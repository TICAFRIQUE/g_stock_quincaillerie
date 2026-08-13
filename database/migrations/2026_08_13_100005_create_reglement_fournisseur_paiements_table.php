<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Même structure que reglement_paiements : un règlement fournisseur peut
     * combiner plusieurs moyens de paiement (paiement mixte).
     */
    public function up(): void
    {
        Schema::create('reglement_fournisseur_paiements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reglement_fournisseur_id')->constrained('reglement_fournisseurs')->restrictOnDelete();
            $table->foreignId('moyen_paiement_id')->constrained('moyen_paiements')->restrictOnDelete();
            $table->unsignedInteger('montant');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reglement_fournisseur_paiements');
    }
};
