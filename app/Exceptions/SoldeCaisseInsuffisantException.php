<?php

namespace App\Exceptions;

use App\Models\SessionCaisse;
use RuntimeException;

class SoldeCaisseInsuffisantException extends RuntimeException
{
    public function __construct(
        public readonly SessionCaisse $session,
        public readonly int $montantDemande,
        public readonly int $soldeTheorique,
    ) {
        parent::__construct(
            "La sortie ({$montantDemande} F) dépasse le solde théorique du tiroir ({$soldeTheorique} F)."
        );
    }
}
