<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Retour client, immuable : toujours lié à une vente précise, jamais un
     * avoir libre. Ne mouvemente jamais la caisse (l'avoir crédite le compte
     * client, voir CompteClientService::crediterRetour()) — voir CLAUDE.md.
     */
    public function up(): void
    {
        Schema::create('retour_ventes', function (Blueprint $table) {
            $table->id();
            $table->string('numero')->unique();
            $table->foreignId('vente_id')->constrained('ventes')->restrictOnDelete();
            $table->foreignId('client_id')->constrained('clients')->restrictOnDelete();
            $table->string('motif')->nullable();
            $table->unsignedInteger('montant_total');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->index('vente_id');
            $table->index('client_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retour_ventes');
    }
};
