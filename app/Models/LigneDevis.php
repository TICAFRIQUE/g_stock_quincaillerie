<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['devis_id', 'produit_id', 'unite_vente_id', 'taxe_id', 'quantite', 'remise_type', 'remise_valeur'])]
class LigneDevis extends Model
{
    protected function casts(): array
    {
        return [
            'quantite' => 'decimal:3',
            'remise_valeur' => 'integer',
        ];
    }

    public function devis(): BelongsTo
    {
        return $this->belongsTo(Devis::class);
    }

    /**
     * withTrashed() : un devis reste consultable même si le produit a été
     * désactivé/supprimé (soft delete) depuis sa création.
     */
    public function produit(): BelongsTo
    {
        return $this->belongsTo(Produit::class)->withTrashed();
    }

    public function uniteVente(): BelongsTo
    {
        return $this->belongsTo(UniteVente::class)->withTrashed();
    }

    /**
     * withTrashed() : un devis reste consultable même si la taxe a été
     * désactivée/supprimée depuis sa création. Contrairement au prix, la
     * taxe choisie n'est jamais recalculée au catalogue courant (règle 15) :
     * c'est un choix explicite du vendeur, pas un montant indicatif.
     */
    public function taxe(): BelongsTo
    {
        return $this->belongsTo(Taxe::class)->withTrashed();
    }
}
