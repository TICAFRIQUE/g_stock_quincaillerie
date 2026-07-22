<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Une vente annulée n'est jamais supprimée ni modifiée dans son contenu
     * (lignes, montants) — seul un marqueur d'annulation (deleted_at, motif,
     * auteur) est ajouté. Le stock est remis à jour via un nouveau mouvement
     * (immuable, type "annulation"), jamais en touchant les mouvements
     * existants.
     */
    public function up(): void
    {
        Schema::table('ventes', function (Blueprint $table) {
            $table->softDeletes();
            $table->text('motif_annulation')->nullable();
            $table->foreignId('annulee_par')->nullable()->constrained('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ventes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('annulee_par');
            $table->dropColumn(['deleted_at', 'motif_annulation']);
        });
    }
};
