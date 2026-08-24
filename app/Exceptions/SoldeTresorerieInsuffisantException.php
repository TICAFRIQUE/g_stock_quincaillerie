<?php

namespace App\Exceptions;

use App\Models\CompteTresorerie;
use RuntimeException;

class SoldeTresorerieInsuffisantException extends RuntimeException
{
    public function __construct(
        public readonly CompteTresorerie $compte,
        public readonly int $montantDemande,
        public readonly int $soldeActuel,
    ) {
        parent::__construct(
            "La sortie ({$montantDemande} F) dépasse le solde de {$compte->nom} ({$soldeActuel} F)."
        );
    }
}
