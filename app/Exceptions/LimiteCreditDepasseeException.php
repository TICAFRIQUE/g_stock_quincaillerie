<?php

namespace App\Exceptions;

use App\Models\Client;
use RuntimeException;

class LimiteCreditDepasseeException extends RuntimeException
{
    public function __construct(
        public readonly Client $client,
        public readonly int $soldeApresVente,
        public readonly int $limite,
    ) {
        parent::__construct(
            "Cette vente à crédit dépasse la limite de crédit de {$client->nom} : ".
            "solde après vente {$soldeApresVente} F, limite {$limite} F."
        );
    }
}
