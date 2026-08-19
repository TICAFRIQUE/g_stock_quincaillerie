<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remboursement (total ou partiel) d'un avoir client, immuable comme un
     * règlement. session_caisse_id nullable : requis seulement si une partie
     * du remboursement sort en espèces (voir RemboursementAvoirClientService)
     * — un remboursement par virement/mobile money n'impacte pas le tiroir.
     */
    public function up(): void
    {
        Schema::create('remboursement_avoir_clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->restrictOnDelete();
            $table->foreignId('session_caisse_id')->nullable()->constrained('session_caisses')->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->unsignedInteger('montant');
            $table->timestamp('created_at')->useCurrent();
            $table->index('client_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('remboursement_avoir_clients');
    }
};
