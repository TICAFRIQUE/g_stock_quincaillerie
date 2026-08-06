<?php

namespace App\Exceptions;

use App\Models\Devis;
use RuntimeException;

class DevisNonTransformableException extends RuntimeException
{
    public function __construct(public readonly Devis $devis)
    {
        parent::__construct(
            "Le devis « {$devis->numero} » ne peut pas être transformé en vente (statut : {$devis->statutEffectif()->libelle()})."
        );
    }
}
