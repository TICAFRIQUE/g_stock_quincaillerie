<?php

namespace App\Exceptions;

use App\Models\Fournisseur;
use RuntimeException;

class SoldeFournisseurInsuffisantException extends RuntimeException
{
    public function __construct(
        public readonly Fournisseur $fournisseur,
        public readonly int $montantDemande,
        public readonly int $solde,
    ) {
        parent::__construct(
            "Le règlement ({$montantDemande} F) dépasse la dette due à {$fournisseur->nom} ({$solde} F)."
        );
    }
}
