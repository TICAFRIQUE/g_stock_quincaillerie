<?php

namespace App\Exceptions;

use RuntimeException;

class SessionNonOuverteException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Aucune session de caisse ouverte : impossible de vendre.');
    }
}
