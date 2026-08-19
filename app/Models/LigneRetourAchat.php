<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['retour_achat_id', 'ligne_commande_achat_id', 'produit_id', 'magasin_id', 'quantite_pieces', 'montant'])]
class LigneRetourAchat extends Model
{
    use HasFactory;

    const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'quantite_pieces' => 'integer',
            'montant' => 'integer',
        ];
    }

    public function retourAchat(): BelongsTo
    {
        return $this->belongsTo(RetourAchat::class);
    }

    public function ligneCommandeAchat(): BelongsTo
    {
        return $this->belongsTo(LigneCommandeAchat::class);
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
