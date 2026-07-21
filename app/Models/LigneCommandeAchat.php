<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['commande_achat_id', 'produit_id', 'quantite', 'prix_achat'])]
class LigneCommandeAchat extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'quantite' => 'integer',
            'prix_achat' => 'integer',
        ];
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
}
