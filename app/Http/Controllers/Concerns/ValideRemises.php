<?php

namespace App\Http\Controllers\Concerns;

use Closure;
use Illuminate\Http\Request;
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

    /**
     * Sans la permission vente.remise, aucune remise ne doit jamais être
     * appliquée — on efface les champs remise avant validation plutôt que de
     * faire confiance à l'interface (qui les masque déjà), au cas où une
     * requête serait construite à la main pour contourner l'écran.
     * Le superadmin passe toujours (bypass Gate::before).
     */
    protected function bloquerRemiseSansPermission(Request $request): void
    {
        if ($request->user()->can('vente.remise')) {
            return;
        }

        $lignes = collect($request->input('lignes', []))->map(function (array $ligne) {
            $ligne['remise_type'] = null;
            $ligne['remise_valeur'] = null;

            return $ligne;
        })->all();

        $request->merge([
            'lignes' => $lignes,
            'remise_totale_type' => null,
            'remise_totale_valeur' => null,
        ]);
    }
}
