<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Seule table transactionnelle réellement mutable : un panier en attente
        // s'édite (ajout/retrait de lignes) jusqu'à sa reprise ou son annulation.
        Schema::create('vente_en_attentes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('magasin_id')->constrained('magasins')->restrictOnDelete();
            $table->foreignId('caisse_id')->constrained('caisses')->restrictOnDelete();
            $table->foreignId('session_caisse_id')->constrained('session_caisses')->restrictOnDelete();
            $table->foreignId('caissier_id')->constrained('users')->restrictOnDelete();
            $table->string('libelle')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vente_en_attentes');
    }
};
