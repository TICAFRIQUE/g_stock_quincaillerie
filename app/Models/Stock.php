<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['produit_id', 'magasin_id', 'quantite', 'cout_moyen_pondere', 'alerte_seuil_envoyee_at'])]
class Stock extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'quantite' => 'decimal:3',
            'cout_moyen_pondere' => 'integer',
            'alerte_seuil_envoyee_at' => 'datetime',
        ];
    }

    /**
     * withTrashed() : la ligne de stock reste consultable (rapports,
     * historique) même si le produit ou le magasin ont été supprimés
     * (soft delete) depuis.
     */
    public function produit(): BelongsTo
    {
        return $this->belongsTo(Produit::class)->withTrashed();
    }

    public function magasin(): BelongsTo
    {
        return $this->belongsTo(Magasin::class)->withTrashed();
    }
}
