<?php

namespace App\Exceptions;

use App\Models\Fournisseur;
use RuntimeException;

class AvoirFournisseurInsuffisantException extends RuntimeException
{
    public function __construct(
        public readonly Fournisseur $fournisseur,
        public readonly int $montantDemande,
        public readonly int $avoirDisponible,
    ) {
        parent::__construct(
            "Le remboursement ({$montantDemande} F) dépasse l'avoir de {$fournisseur->nom} ({$avoirDisponible} F)."
        );
    }
}
