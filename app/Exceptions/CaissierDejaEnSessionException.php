<?php

namespace App\Exceptions;

use App\Models\SessionCaisse;
use RuntimeException;

class CaissierDejaEnSessionException extends RuntimeException
{
    public function __construct(public readonly SessionCaisse $sessionOuverte)
    {
        parent::__construct(
            "Vous avez déjà une session ouverte sur « {$sessionOuverte->caisse->nom} ». Fermez-la avant d'en ouvrir une autre."
        );
    }
}
