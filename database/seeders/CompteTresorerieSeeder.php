<?php

namespace Database\Seeders;

use App\Models\CompteTresorerie;
use Illuminate\Database\Seeder;

class CompteTresorerieSeeder extends Seeder
{
    /**
     * Singleton : la Caisse Générale doit toujours exister, un seul
     * enregistrement de type caisse_generale (voir CLAUDE.md, Trésorerie).
     */
    public function run(): void
    {
        CompteTresorerie::firstOrCreate(
            ['type' => 'caisse_generale'],
            ['nom' => 'Caisse Générale', 'actif' => true]
        );
    }
}
