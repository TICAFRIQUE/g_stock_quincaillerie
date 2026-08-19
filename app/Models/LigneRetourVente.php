<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['retour_vente_id', 'ligne_vente_id', 'produit_id', 'magasin_id', 'quantite_pieces', 'montant', 'cout_applique'])]
class LigneRetourVente extends Model
{
    use HasFactory;

    const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'quantite_pieces' => 'integer',
            'montant' => 'integer',
            'cout_applique' => 'integer',
        ];
    }

    public function retourVente(): BelongsTo
    {
        return $this->belongsTo(RetourVente::class);
    }

    public function ligneVente(): BelongsTo
    {
        return $this->belongsTo(LigneVente::class);
    }

    /**
     * withTrashed() : ligne historique, doit rester affichable même si le
     * produit a été supprimé (soft delete) depuis.
     */
    public function produit(): BelongsTo
    {
        return $this->belongsTo(Produit::class)->withTrashed();
    }

    /**
     * withTrashed() : ligne historique, doit rester affichable même si le
     * magasin/dépôt a été supprimé depuis.
     */
    public function magasin(): BelongsTo
    {
        return $this->belongsTo(Magasin::class)->withTrashed();
    }
}
