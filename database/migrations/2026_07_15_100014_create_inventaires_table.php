<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventaires', function (Blueprint $table) {
            $table->id();
            $table->foreignId('magasin_id')->constrained('magasins')->restrictOnDelete();
            $table->string('statut')->default('brouillon'); // brouillon | valide
            $table->date('date');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('valide_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('valide_at')->nullable();
            $table->timestamps();
            $table->index('statut');
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventaires');
    }
};
