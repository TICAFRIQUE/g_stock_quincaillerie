<?php

namespace App\Models;

use App\Support\Arrondi;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['reception_achat_id', 'ligne_commande_achat_id', 'produit_id', 'magasin_id', 'quantite_pieces', 'prix_achat_reel', 'taxe_id'])]
class LigneReceptionAchat extends Model
{
    use HasFactory;

    const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'quantite_pieces' => 'decimal:3',
            'prix_achat_reel' => 'integer',
        ];
    }

    public function receptionAchat(): BelongsTo
    {
        return $this->belongsTo(ReceptionAchat::class);
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

    /**
     * withTrashed() : ligne historique, doit rester affichable même si la
     * taxe a été désactivée/supprimée depuis.
     */
    public function taxe(): BelongsTo
    {
        return $this->belongsTo(Taxe::class)->withTrashed();
    }

    /**
     * prix_achat_reel est HT, par pièce (unité de base) — pas de conversion
     * d'unité d'achat ici, contrairement à LigneCommandeAchat.
     */
    public function montantHt(): int
    {
        return Arrondi::entier($this->prix_achat_reel * (float) $this->quantite_pieces);
    }

    public function montantTtc(): int
    {
        $ht = $this->montantHt();

        return $ht + Arrondi::entier($ht * ($this->taxe?->taux ?? 0) / 100);
    }
}
