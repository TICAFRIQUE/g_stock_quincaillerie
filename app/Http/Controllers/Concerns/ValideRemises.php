<?php

namespace App\Http\Controllers\Concerns;

use Closure;
use Illuminate\Support\Str;

/**
 * Une remise en pourcentage au-delà de 100 n'a pas de sens (le montant
 * résolu est déjà plafonné à la base dans VenteService, mais on bloque la
 * saisie en amont plutôt que de laisser une valeur absurde être stockée).
 */
trait ValideRemises
{
    protected function remisePourcentageMax(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) {
            $champType = Str::replaceLast('_valeur', '_type', $attribute);

            if (request()->input($champType) === 'pourcentage' && $value > 100) {
                $fail('Une remise en pourcentage ne peut pas dépasser 100.');
            }
        };
    }
}
