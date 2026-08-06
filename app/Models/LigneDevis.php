<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['devis_id', 'produit_id', 'unite_vente_id', 'quantite', 'remise_type', 'remise_valeur'])]
class LigneDevis extends Model
{
    protected function casts(): array
    {
        return [
            'quantite' => 'integer',
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
}
