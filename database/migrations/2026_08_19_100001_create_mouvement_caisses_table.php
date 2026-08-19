<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mouvement de caisse manuel (entrée/sortie de tiroir), immuable —
     * mirroring MouvementStock pour le stock. Toujours rattaché à une
     * session ouverte (comme une vente). montant toujours positif, la
     * direction est portée par `type`. reference optionnelle : pointe vers
     * un ReglementFournisseur quand la sortie a été générée automatiquement
     * par un règlement payé en espèces.
     */
    public function up(): void
    {
        Schema::create('mouvement_caisses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_caisse_id')->constrained('session_caisses')->restrictOnDelete();
            $table->string('type'); // entree | sortie
            $table->unsignedInteger('montant');
            $table->string('motif');
            $table->nullableMorphs('reference');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->index('session_caisse_id');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mouvement_caisses');
    }
};
