<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Table singleton (une seule ligne, id=1) : coordonnées de contact affichées
 * sur "Mon abonnement" quand l'abonnement a expiré. Toujours passer par
 * actuel(), jamais un ::find(1) direct — même principe que Parametre::actuel().
 */
#[Fillable(['telephone', 'whatsapp', 'message'])]
class ConfigurationAbonnement extends Model
{
    use LogsActivity;

    public const CACHE_KEY = 'configuration_abonnement.actuel';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    public static function actuel(): self
    {
        $attributs = Cache::rememberForever(self::CACHE_KEY, function () {
            return self::query()->firstOrCreate(['id' => 1])->getAttributes();
        });

        return (new self)->newFromBuilder($attributs);
    }

    public static function invaliderCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    protected static function booted(): void
    {
        static::saved(fn () => self::invaliderCache());
    }
}
