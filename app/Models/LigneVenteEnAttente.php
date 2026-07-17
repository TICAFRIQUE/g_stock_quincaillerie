<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['vente_en_attente_id', 'produit_id', 'unite_vente_id', 'quantite'])]
class LigneVenteEnAttente extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'quantite' => 'integer',
        ];
    }

    public function venteEnAttente(): BelongsTo
    {
        return $this->belongsTo(VenteEnAttente::class);
    }

    public function produit(): BelongsTo
    {
        return $this->belongsTo(Produit::class);
    }

    public function uniteVente(): BelongsTo
    {
        return $this->belongsTo(UniteVente::class);
    }
}
