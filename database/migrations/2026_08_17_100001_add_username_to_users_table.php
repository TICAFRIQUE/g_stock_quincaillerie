<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Connexion par nom d'utilisateur + code à 4 chiffres au lieu d'e-mail +
     * mot de passe : l'e-mail reste en base (optionnel) pour une utilisation
     * future (alertes), mais n'est plus l'identifiant de connexion.
     * nullable ici car pas de doctrine/dbal disponible pour un ->change() ;
     * les comptes existants sont complétés via une commande applicative
     * (jamais NULL en pratique après la mise en service).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->unique()->after('name');
        });

        DB::statement('ALTER TABLE users MODIFY email VARCHAR(255) NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE users MODIFY email VARCHAR(255) NOT NULL');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('username');
        });
    }
};
