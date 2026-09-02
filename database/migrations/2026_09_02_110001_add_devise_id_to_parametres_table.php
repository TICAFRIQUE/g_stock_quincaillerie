<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parametres', function (Blueprint $table) {
            $table->foreignId('devise_id')->nullable()->after('nom')->constrained('devises')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('parametres', function (Blueprint $table) {
            $table->dropConstrainedForeignId('devise_id');
        });
    }
};
