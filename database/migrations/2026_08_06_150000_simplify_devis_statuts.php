<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Simplification du workflow devis : plus d'étape « envoyé »/« accepté »
     * séparée (voir DevisStatut). Les devis existants dans l'un de ces deux
     * statuts (donc pas encore transformés) redeviennent des brouillons —
     * toujours transformables, rien n'est perdu. Sans cette migration,
     * l'enum PHP refuserait de les hydrater (ValueError) et l'application
     * planterait sur ces lignes.
     */
    public function up(): void
    {
        DB::table('devis')->whereIn('statut', ['envoye', 'accepte'])->update(['statut' => 'brouillon']);
    }

    public function down(): void
    {
        // Non réversible avec certitude (on ne sait plus lequel des deux
        // statuts d'origine s'appliquait) : ne rien tenter plutôt que de
        // deviner.
    }
};
