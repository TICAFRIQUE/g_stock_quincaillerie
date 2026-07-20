<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

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

    protected static function booted(): void
    {
        // Le catalogue de vente en cache embarque les unités de vente actives
        // de chaque produit — toute écriture ici doit aussi l'invalider.
        $invalider = fn () => Cache::forget(Produit::CACHE_CATALOGUE_VENTE);

        static::saved($invalider);
        static::deleted($invalider);
        static::restored($invalider);
    }
}
