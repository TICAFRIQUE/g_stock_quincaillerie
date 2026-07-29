<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table singleton (une seule ligne, id=1) : configuration globale de
     * l'application (logo via medialibrary, nom, slogan, coordonnées). Voir
     * Parametre::actuel().
     */
    public function up(): void
    {
        Schema::create('parametres', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('slogan')->nullable();
            $table->string('numero')->nullable();
            $table->string('adresse')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parametres');
    }
};
