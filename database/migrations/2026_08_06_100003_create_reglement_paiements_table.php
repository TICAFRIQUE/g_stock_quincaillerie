<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Même structure que paiements/ventes : un règlement peut combiner
        // plusieurs moyens de paiement (paiement mixte).
        Schema::create('reglement_paiements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reglement_client_id')->constrained('reglement_clients')->restrictOnDelete();
            $table->foreignId('moyen_paiement_id')->constrained('moyen_paiements')->restrictOnDelete();
            $table->unsignedInteger('montant');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reglement_paiements');
    }
};
