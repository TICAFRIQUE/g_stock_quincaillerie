<?php

namespace App\Exceptions;

use App\Models\Devis;
use RuntimeException;

class DevisNonModifiableException extends RuntimeException
{
    public function __construct(public readonly Devis $devis)
    {
        parent::__construct(
            "Le devis « {$devis->numero} » n'est plus modifiable (statut : {$devis->statut->libelle()})."
        );
    }
}
