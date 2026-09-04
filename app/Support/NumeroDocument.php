<?php

namespace App\Support;

/**
 * Numéro de document lisible unique (préfixe + 6 chiffres aléatoires), même
 * format pour tous les documents commerciaux (BC-, BA-, RF-…) — un seul
 * endroit pour cette génération plutôt qu'une boucle dupliquée par contrôleur/
 * service.
 */
class NumeroDocument
{
    public static function genererUnique(string $prefixe, string $modelClass, string $colonne = 'numero'): string
    {
        do {
            $numero = $prefixe.'-'.str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
        } while ($modelClass::where($colonne, $numero)->exists());

        return $numero;
    }
}
