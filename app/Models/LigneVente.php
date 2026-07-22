<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'vente_id', 'produit_id', 'unite_vente_id', 'quantite', 'quantite_pieces',
    'prix_unitaire_applique', 'cout_applique', 'sous_total_ligne',
    'remise_ligne_type', 'remise_ligne_valeur', 'remise_ligne_montant', 'total_ligne',
])]
class LigneVente extends Model
{
    use HasFactory;

    const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'quantite' => 'integer',
            'quantite_pieces' => 'integer',
            'prix_unitaire_applique' => 'integer',
            'cout_applique' => 'integer',
            'sous_total_ligne' => 'integer',
            'remise_ligne_valeur' => 'integer',
            'remise_ligne_montant' => 'integer',
            'total_ligne' => 'integer',
        ];
    }

    public function vente(): BelongsTo
    {
        return $this->belongsTo(Vente::class)->withTrashed();
    }

    /**
     * withTrashed() : une ligne de vente est immuable et doit rester
     * affichable (ticket, rapport de marge) même si le produit a été
     * supprimé (soft delete) depuis.
     */
    public function produit(): BelongsTo
    {
        return $this->belongsTo(Produit::class)->withTrashed();
    }

    public function uniteVente(): BelongsTo
    {
        return $this->belongsTo(UniteVente::class)->withTrashed();
    }
}
