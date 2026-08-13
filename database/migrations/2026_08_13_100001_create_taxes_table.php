<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taxes', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            // Pourcentage entier (pas de flottant, cohérent avec les montants XOF).
            $table->unsignedInteger('taux');
            $table->boolean('actif')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->index('nom');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taxes');
    }
};
