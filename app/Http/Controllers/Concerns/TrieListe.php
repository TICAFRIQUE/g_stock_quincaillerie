<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Tri de colonne partagé par les listes DataTables-like du back-office :
 * lit ?tri=&direction= dans la requête, valide contre une liste blanche de
 * colonnes triables, et retombe sur le tri par défaut (le plus récent
 * d'abord) sinon.
 *
 * $colonnesTriables accepte soit une liste simple (['nom', 'actif']), soit
 * un tableau associatif (['nom' => 'produits.nom']) quand la requête est
 * jointe et que le nom de colonne exposé au client (?tri=nom) doit être
 * qualifié pour rester non ambigu en SQL.
 */
trait TrieListe
{
    protected function appliquerTri(Builder $query, Request $request, array $colonnesTriables, string $colonneDefaut = 'created_at', string $directionDefaut = 'desc'): Builder
    {
        $tri = $request->string('tri')->toString();
        $direction = $request->string('direction')->toString() === 'asc' ? 'asc' : 'desc';

        $colonneReelle = $colonnesTriables[$tri] ?? (in_array($tri, $colonnesTriables, true) ? $tri : null);

        if ($colonneReelle !== null) {
            return $query->orderBy($colonneReelle, $direction);
        }

        return $query->orderBy($colonneDefaut, $directionDefaut);
    }
}
