<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['produit_id', 'libelle', 'facteur', 'prix', 'actif'])]
class UniteVente extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'facteur' => 'integer',
            'prix' => 'integer',
            'actif' => 'boolean',
        ];
    }

    public function produit(): BelongsTo
    {
        return $this->belongsTo(Produit::class);
    }
}
