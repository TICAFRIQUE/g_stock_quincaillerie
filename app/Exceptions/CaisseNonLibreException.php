<?php

namespace App\Exceptions;

use App\Models\Caisse;
use RuntimeException;

class CaisseNonLibreException extends RuntimeException
{
    public function __construct(public readonly Caisse $caisse)
    {
        parent::__construct("La caisse « {$caisse->nom} » a déjà une session ouverte.");
    }
}
