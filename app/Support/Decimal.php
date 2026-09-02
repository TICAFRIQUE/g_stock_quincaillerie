<?php

namespace App\Support;

/**
 * Normalisation d'une quantité saisie au clavier — un point ou une virgule
 * doivent tous les deux être acceptés (habitude locale variable), toujours
 * ramenés au point avant validation ('numeric') et stockage.
 */
class Decimal
{
    public static function normaliser(mixed $valeur): mixed
    {
        if (! is_string($valeur)) {
            return $valeur;
        }

        return str_replace(',', '.', trim($valeur));
    }
}
