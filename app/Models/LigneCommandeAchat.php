<?php

namespace App\Models;

use App\Support\Arrondi;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * quantite_pieces n'est volontairement pas fillable : c'est un champ dérivé,
 * calculé côté serveur (voir booted()), jamais saisi directement.
 */
#[Fillable(['commande_achat_id', 'produit_id', 'unite_achat', 'qte_par_groupe', 'quantite', 'prix_achat'])]
class LigneCommandeAchat extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'qte_par_groupe' => 'integer',
            'quantite' => 'integer',
            'quantite_pieces' => 'integer',
            'prix_achat' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (LigneCommandeAchat $ligne) {
            $ligne->quantite_pieces = $ligne->quantite * $ligne->facteurPieces();
        });
    }

    public function commandeAchat(): BelongsTo
    {
        return $this->belongsTo(CommandeAchat::class);
    }

    /**
     * withTrashed() : ligne de commande historique, doit rester affichable
     * même si le produit a été supprimé (soft delete) depuis.
     */
    public function produit(): BelongsTo
    {
        return $this->belongsTo(Produit::class)->withTrashed();
    }

    /**
     * Nombre de pièces contenues dans une unité d'achat (1 pour "pièce",
     * qte_par_groupe pour "groupe").
     */
    public function facteurPieces(): int
    {
        return $this->unite_achat === 'groupe' ? max(1, (int) $this->qte_par_groupe) : 1;
    }

    /**
     * prix_achat porte sur l'unité d'achat saisie (le groupe entier le cas
     * échéant) : ramené au prix par pièce pour alimenter le CMP.
     */
    public function prixAchatParPiece(): int
    {
        $facteur = $this->facteurPieces();

        return $facteur > 1 ? Arrondi::entier($this->prix_achat / $facteur) : $this->prix_achat;
    }
}
