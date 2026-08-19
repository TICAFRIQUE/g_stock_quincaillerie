<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remboursement (total ou partiel) d'un avoir fournisseur — le
     * fournisseur nous reverse ce qu'il nous doit. Immuable, symétrique de
     * remboursement_avoir_clients. session_caisse_id nullable : requis
     * seulement si une partie est reçue en espèces (entrée de caisse, voir
     * RemboursementAvoirFournisseurService).
     */
    public function up(): void
    {
        Schema::create('remboursement_avoir_fournisseurs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fournisseur_id')->constrained('fournisseurs')->restrictOnDelete();
            $table->foreignId('session_caisse_id')->nullable()->constrained('session_caisses')->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->unsignedInteger('montant');
            $table->timestamp('created_at')->useCurrent();
            $table->index('fournisseur_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('remboursement_avoir_fournisseurs');
    }
};
