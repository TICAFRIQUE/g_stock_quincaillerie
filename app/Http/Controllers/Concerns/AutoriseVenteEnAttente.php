<?php

namespace App\Http\Controllers\Concerns;

use App\Models\VenteEnAttente;

/**
 * CLAUDE.md : une vente en attente est rattachée au caissier qui l'a créée —
 * il est seul à pouvoir la reprendre/la modifier. Le gérant (permission
 * caisse.gerer) peut voir et annuler celles de son magasin, mais pas les
 * finaliser à la place du caissier propriétaire.
 */
trait AutoriseVenteEnAttente
{
    protected function assurerProprietaire(VenteEnAttente $venteEnAttente): void
    {
        abort_if(
            $venteEnAttente->caissier_id !== request()->user()->id,
            403,
            'Cette vente en attente appartient à un autre caissier — seul son propriétaire peut la reprendre.'
        );
    }

    protected function assurerProprietaireOuGerant(VenteEnAttente $venteEnAttente): void
    {
        abort_if(
            $venteEnAttente->caissier_id !== request()->user()->id && ! request()->user()->can('caisse.gerer'),
            403,
            'Cette vente en attente appartient à un autre caissier.'
        );
    }
}
