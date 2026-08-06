<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

#[Fillable(['produit_id', 'unite_id', 'facteur', 'prix', 'actif'])]
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

    /**
     * Compatibilité : historiquement une colonne texte libre générée
     * automatiquement (« Lot de N »), désormais composée à partir du
     * référentiel Unite (voir migration 2026_08_06_130002). Garder ce nom
     * d'accesseur évite de retoucher tous les écrans qui l'affichent déjà
     * (vente, devis, ticket, fiche produit…).
     */
    protected function libelle(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->unite ? "{$this->unite->nom} de {$this->facteur}" : "Lot de {$this->facteur}",
        );
    }

    /**
     * withTrashed() : évite un crash d'affichage si le produit parent a
     * été supprimé (soft delete).
     */
    public function produit(): BelongsTo
    {
        return $this->belongsTo(Produit::class)->withTrashed();
    }

    public function unite(): BelongsTo
    {
        return $this->belongsTo(Unite::class);
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
