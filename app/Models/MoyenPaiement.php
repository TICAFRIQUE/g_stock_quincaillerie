<?php

namespace App\Models;

use App\Models\Concerns\MetEnFormePhrase;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

#[Fillable(['nom', 'est_espece', 'actif'])]
class MoyenPaiement extends Model
{
    use HasFactory, MetEnFormePhrase;

    private const CACHE_ACTIFS = 'moyens-paiement:actifs';

    protected function casts(): array
    {
        return [
            'est_espece' => 'boolean',
            'actif' => 'boolean',
        ];
    }

    protected function nom(): Attribute
    {
        return Attribute::make(set: fn (?string $value) => static::casseEnPhrase($value));
    }

    public function paiements(): HasMany
    {
        return $this->hasMany(Paiement::class);
    }

    /**
     * Donnée de référence peu volatile (change en admin, jamais en usage
     * courant) : mise en cache, invalidée à chaque écriture (voir booted()).
     * Mis en cache sous forme de tableau brut (jamais d'objet Eloquent) car
     * le driver cache "database" interdit l'unserialize d'objets PHP
     * (`config('cache.serializable_classes')` = false, durcissement contre
     * l'injection d'objets) : un objet caché reviendrait `__PHP_Incomplete_Class`.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, self>
     */
    public static function actifs(): \Illuminate\Database\Eloquent\Collection
    {
        $attributs = Cache::rememberForever(
            self::CACHE_ACTIFS,
            fn () => static::where('actif', true)->orderBy('nom')->get()->toArray()
        );

        return static::hydrate($attributs);
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::CACHE_ACTIFS));
        static::deleted(fn () => Cache::forget(self::CACHE_ACTIFS));
    }
}
