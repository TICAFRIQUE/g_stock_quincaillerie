<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Document commercial pré-vente, créé hors caisse (aucun mouvement de
        // stock ni d'argent) — voir CLAUDE.md, section « Devis ». Mutable tant
        // que statut = brouillon ; figé dès qu'il est transformé.
        Schema::create('devis', function (Blueprint $table) {
            $table->id();
            $table->string('numero')->unique(); // M{magasin}-D-{séquence}
            $table->foreignId('magasin_id')->constrained('magasins')->restrictOnDelete();
            $table->foreignId('client_id')->constrained('clients')->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('statut')->default('brouillon'); // brouillon|envoye|accepte|refuse|transforme
            $table->date('date_validite');
            $table->foreignId('vente_id')->nullable()->constrained('ventes')->restrictOnDelete();
            $table->timestamp('transforme_at')->nullable();
            $table->timestamps();
            $table->index('statut');
            $table->index('date_validite');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devis');
    }
};
