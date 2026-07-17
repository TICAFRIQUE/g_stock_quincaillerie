<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('caisses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('magasin_id')->constrained('magasins')->restrictOnDelete();
            $table->string('nom');
            // Séquence de numérotation des tickets, continue par caisse, jamais remise à zéro.
            $table->unsignedBigInteger('sequence_ventes')->default(0);
            $table->boolean('actif')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['magasin_id', 'nom']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('caisses');
    }
};
