<?php

namespace App\Exceptions;

use App\Models\Client;
use RuntimeException;

class SoldeClientInsuffisantException extends RuntimeException
{
    public function __construct(
        public readonly Client $client,
        public readonly int $montantDemande,
        public readonly int $solde,
    ) {
        parent::__construct(
            "Le règlement ({$montantDemande} F) dépasse la dette de {$client->nom} ({$solde} F)."
        );
    }
}
