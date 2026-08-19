<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('remboursement_avoir_fournisseur_paiements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('remboursement_avoir_fournisseur_id');
            $table->foreign('remboursement_avoir_fournisseur_id', 'raf_paiements_remboursement_fk')
                ->references('id')->on('remboursement_avoir_fournisseurs')->restrictOnDelete();
            $table->foreignId('moyen_paiement_id');
            $table->foreign('moyen_paiement_id', 'raf_paiements_moyen_fk')
                ->references('id')->on('moyen_paiements')->restrictOnDelete();
            $table->unsignedInteger('montant');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('remboursement_avoir_fournisseur_paiements');
    }
};
