<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Encaissement d'une dette client, immuable comme une vente. Doit
        // toujours appartenir à une session de caisse ouverte au moment de
        // l'encaissement (règle 14) — les espèces comptent dans le tiroir.
        Schema::create('reglement_clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->restrictOnDelete();
            $table->foreignId('session_caisse_id')->constrained('session_caisses')->restrictOnDelete();
            $table->foreignId('caissier_id')->constrained('users')->restrictOnDelete();
            $table->unsignedInteger('montant');
            $table->timestamp('created_at')->useCurrent();
            $table->index('client_id');
            $table->index('session_caisse_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reglement_clients');
    }
};
