<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Transfert simple, sans valorisation : sortie du magasin source + entrée dans
        // le magasin destination. Génère 2 lignes mouvement_stocks référencées vers lui.
        Schema::create('transferts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produit_id')->constrained('produits')->restrictOnDelete();
            $table->unsignedInteger('quantite');
            $table->foreignId('magasin_source_id')->constrained('magasins')->restrictOnDelete();
            $table->foreignId('magasin_destination_id')->constrained('magasins')->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });

        DB::statement('ALTER TABLE transferts ADD CONSTRAINT chk_transferts_magasins CHECK (magasin_source_id <> magasin_destination_id)');
    }

    public function down(): void
    {
        Schema::dropIfExists('transferts');
    }
};
