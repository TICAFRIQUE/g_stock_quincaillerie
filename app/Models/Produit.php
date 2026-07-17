<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[Fillable([
    'sku', 'nom', 'libelle_distinctif', 'code_barre', 'categorie_id',
    'prix_piece', 'seuil_alerte', 'actif',
])]
class Produit extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, LogsActivity, InteractsWithMedia;

    protected function casts(): array
    {
        return [
            'prix_piece' => 'integer',
            'seuil_alerte' => 'integer',
            'actif' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    /**
     * Libellé unique réutilisé partout où un produit est affiché (listes,
     * select, ticket…) : "Nom" seul, ou "Nom — Libellé distinctif" quand ce
     * dernier est renseigné. Évite de dupliquer la logique d'affichage.
     */
    protected function libelleAffichage(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->libelle_distinctif
                ? "{$this->nom} — {$this->libelle_distinctif}"
                : $this->nom,
        );
    }

    /**
     * Idem, avec le prix pièce accolé (utilisé dans les select produit).
     */
    protected function libelleAvecPrix(): Attribute
    {
        return Attribute::make(
            get: fn () => "{$this->libelle_affichage} — ".number_format($this->prix_piece, 0, ',', ' ').' F',
        );
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')->singleFile();
    }

    public function categorie(): BelongsTo
    {
        return $this->belongsTo(Categorie::class);
    }

    public function uniteVentes(): HasMany
    {
        return $this->hasMany(UniteVente::class);
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    public function mouvementStocks(): HasMany
    {
        return $this->hasMany(MouvementStock::class);
    }

    public function ligneVentes(): HasMany
    {
        return $this->hasMany(LigneVente::class);
    }
}
