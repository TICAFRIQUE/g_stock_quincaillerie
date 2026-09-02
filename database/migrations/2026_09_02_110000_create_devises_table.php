<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Référentiel d'affichage uniquement — aucune conversion, aucun taux.
     * Les montants restent des entiers (francs) partout, quelle que soit la
     * devise choisie : seule l'abréviation affichée change (voir
     * Devise::abreviationActuelle(), fonction montant() dans app/helpers.php).
     */
    public function up(): void
    {
        Schema::create('devises', function (Blueprint $table) {
            $table->id();
            $table->string('nom')->unique();
            $table->string('abreviation');
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devises');
    }
};
