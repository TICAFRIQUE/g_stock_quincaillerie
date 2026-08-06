<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[Fillable([
    'sku', 'nom', 'libelle_distinctif', 'code_barre', 'categorie_id',
    'prix_piece', 'unite_base_id', 'seuil_alerte', 'actif',
])]
class Produit extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, LogsActivity, InteractsWithMedia;

    /**
     * Clé de cache du catalogue de vente (voir catalogueVente()) — publique
     * pour que UniteVente puisse invalider la même entrée sans dupliquer la
     * clé (le catalogue en cache embarque les unités de vente).
     */
    public const CACHE_CATALOGUE_VENTE = 'produits:catalogue-vente';

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

    /**
     * Compatibilité : historiquement une colonne texte libre, désormais
     * dérivée du référentiel Unite (voir migration 2026_08_06_130001).
     * Garder ce nom d'accesseur évite de retoucher tous les écrans qui
     * l'affichent déjà (vente, devis, ticket…).
     */
    protected function uniteBaseLibelle(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->uniteBase
                ? ($this->uniteBase->abbreviation ?: $this->uniteBase->nom)
                : 'pièce',
        );
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')->singleFile();
    }

    /**
     * withTrashed() : évite un crash d'affichage si la catégorie a été
     * supprimée (soft delete) alors que des produits la référencent encore.
     */
    public function categorie(): BelongsTo
    {
        return $this->belongsTo(Categorie::class)->withTrashed();
    }

    public function uniteBase(): BelongsTo
    {
        return $this->belongsTo(Unite::class, 'unite_base_id');
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

    /**
     * Référentiel produits pour l'écran de vente : donnée de référence peu
     * volatile (change en admin, jamais à la vente), mise en cache et
     * invalidée à chaque écriture (voir booted()). Ne contient jamais le
     * stock — dérivé des mouvements, donc toujours recalculé en direct par
     * l'appelant, jamais mis en cache avec le catalogue.
     *
     * Mis en cache sous forme de tableau brut (jamais d'objet
     * Collection/Model) car le driver cache "database" interdit
     * l'unserialize d'objets PHP (`config('cache.serializable_classes')` =
     * false, durcissement contre l'injection d'objets) : un objet caché
     * reviendrait `__PHP_Incomplete_Class`.
     *
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    public static function catalogueVente(): \Illuminate\Support\Collection
    {
        $catalogue = Cache::rememberForever(self::CACHE_CATALOGUE_VENTE, function () {
            return static::where('actif', true)
                ->with([
                    'uniteBase',
                    'uniteVentes' => fn ($q) => $q->where('actif', true)->orderBy('facteur'),
                    'uniteVentes.unite',
                ])
                ->orderBy('nom')
                ->get()
                ->map(fn (self $p) => [
                    'id' => $p->id,
                    'sku' => $p->sku,
                    'nom' => $p->nom,
                    'libelle_distinctif' => $p->libelle_distinctif,
                    'libelle_affichage' => $p->libelle_affichage,
                    'prix_piece' => $p->prix_piece,
                    'unite_base_libelle' => $p->unite_base_libelle,
                    'unites' => $p->uniteVentes->map(fn (UniteVente $u) => [
                        'id' => $u->id,
                        'libelle' => $u->libelle,
                        'facteur' => $u->facteur,
                        'prix' => $u->prix,
                    ])->values()->all(),
                ])
                ->values()
                ->all();
        });

        return collect($catalogue)->map(fn (array $p) => [
            ...$p,
            'unites' => collect($p['unites']),
        ]);
    }

    protected static function booted(): void
    {
        $invalider = fn () => Cache::forget(self::CACHE_CATALOGUE_VENTE);

        static::saved($invalider);
        static::deleted($invalider);
        static::restored($invalider);
    }
}
