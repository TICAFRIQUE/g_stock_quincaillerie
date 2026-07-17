<?php

namespace App\Exceptions;

use RuntimeException;

class VentesEnAttentePresentesException extends RuntimeException
{
    public function __construct(public readonly int $nombre)
    {
        parent::__construct(
            "Impossible de clôturer ou fermer la session : {$nombre} vente(s) en attente doivent d'abord être finalisées ou annulées."
        );
    }
}
