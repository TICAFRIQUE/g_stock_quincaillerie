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
            'quantite' => 'integer',
            'cout_moyen_pondere' => 'integer',
            'alerte_seuil_envoyee_at' => 'datetime',
        ];
    }

    public function produit(): BelongsTo
    {
        return $this->belongsTo(Produit::class);
    }

    public function magasin(): BelongsTo
    {
        return $this->belongsTo(Magasin::class);
    }
}
