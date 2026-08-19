<?php

namespace App\Exceptions;

use App\Models\Client;
use RuntimeException;

class AvoirClientInsuffisantException extends RuntimeException
{
    public function __construct(
        public readonly Client $client,
        public readonly int $montantDemande,
        public readonly int $avoirDisponible,
    ) {
        parent::__construct(
            "Le remboursement ({$montantDemande} F) dépasse l'avoir de {$client->nom} ({$avoirDisponible} F)."
        );
    }
}
