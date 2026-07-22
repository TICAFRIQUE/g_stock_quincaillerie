<?php

namespace App\Http\Controllers\Concerns;

use App\Models\VenteEnAttente;

/**
 * CLAUDE.md : une vente en attente est rattachée au caissier qui l'a créée —
 * un caissier ne voit et ne traite (reprendre/modifier/annuler) que les
 * siennes. Le gérant ou le superadmin (permission caisse.gerer, dont le
 * superadmin dispose toujours via le bypass Gate::before) peuvent voir et
 * traiter celles de n'importe quel caissier de leur périmètre.
 */
trait AutoriseVenteEnAttente
{
    protected function assurerProprietaireOuGerant(VenteEnAttente $venteEnAttente): void
    {
        abort_if(
            $venteEnAttente->caissier_id !== request()->user()->id && ! request()->user()->can('caisse.gerer'),
            403,
            'Cette vente en attente appartient à un autre caissier.'
        );
    }
}
