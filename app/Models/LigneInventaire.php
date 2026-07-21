<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['inventaire_id', 'produit_id', 'quantite_theorique', 'quantite_comptee', 'ecart'])]
class LigneInventaire extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'quantite_theorique' => 'integer',
            'quantite_comptee' => 'integer',
            'ecart' => 'integer',
        ];
    }

    public function inventaire(): BelongsTo
    {
        return $this->belongsTo(Inventaire::class);
    }

    /**
     * withTrashed() : ligne d'inventaire historique, doit rester affichable
     * même si le produit a été supprimé (soft delete) depuis.
     */
    public function produit(): BelongsTo
    {
        return $this->belongsTo(Produit::class)->withTrashed();
    }
}
