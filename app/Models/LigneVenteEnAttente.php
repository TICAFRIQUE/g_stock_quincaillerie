<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['vente_en_attente_id', 'produit_id', 'unite_vente_id', 'magasin_source_id', 'quantite'])]
class LigneVenteEnAttente extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'quantite' => 'decimal:3',
        ];
    }

    public function venteEnAttente(): BelongsTo
    {
        return $this->belongsTo(VenteEnAttente::class);
    }

    /**
     * withTrashed() : évite un crash d'affichage si le produit a été
     * supprimé (soft delete) pendant qu'il restait dans un panier en
     * attente.
     */
    public function produit(): BelongsTo
    {
        return $this->belongsTo(Produit::class)->withTrashed();
    }

    public function uniteVente(): BelongsTo
    {
        return $this->belongsTo(UniteVente::class)->withTrashed();
    }

    public function magasinSource(): BelongsTo
    {
        return $this->belongsTo(Magasin::class, 'magasin_source_id')->withTrashed();
    }
}
