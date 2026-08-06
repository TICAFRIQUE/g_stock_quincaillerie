<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

/**
 * Référentiel central des unités (mesure ou conditionnement), géré en
 * administration — réutilisé pour l'unité de base d'un produit (Litre,
 * Mètre, Kg, Pièce…) et pour le nom des unités de vente (Carton, Bidon,
 * Sac, Rouleau…), voir CLAUDE.md.
 */
#[Fillable(['nom', 'abbreviation', 'actif'])]
class Unite extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'actif' => 'boolean',
        ];
    }

    public function produits(): HasMany
    {
        return $this->hasMany(Produit::class, 'unite_base_id');
    }

    public function uniteVentes(): HasMany
    {
        return $this->hasMany(UniteVente::class);
    }

    protected static function booted(): void
    {
        // Le catalogue de vente en cache embarque le nom des unités (base et
        // unités de vente) : renommer une unité doit aussi l'invalider.
        $invalider = fn () => Cache::forget(Produit::CACHE_CATALOGUE_VENTE);

        static::saved($invalider);
        static::deleted($invalider);
    }
}
