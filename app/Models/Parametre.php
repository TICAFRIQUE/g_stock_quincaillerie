<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * Table singleton : une seule ligne (id=1) porte la configuration globale de
 * l'application. Toujours passer par actuel(), jamais par un ::find(1) direct,
 * pour bénéficier du cache (lu sur presque chaque page : sidebar, connexion,
 * tickets, e-mails).
 */
#[Fillable(['nom', 'slogan', 'numero', 'adresse', 'duree_validite_devis_jours', 'devise_id'])]
class Parametre extends Model implements HasMedia
{
    use InteractsWithMedia, LogsActivity;

    public const CACHE_KEY = 'parametre.actuel';

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')->singleFile();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    /**
     * Ne met en cache que les colonnes brutes (tableau), jamais l'instance de
     * modèle complète : le driver de cache "database" sérialise via
     * serialize()/unserialize(), et l'état interne de InteractsWithMedia (HasMedia)
     * ne survit pas fidèlement à ce round-trip (__PHP_Incomplete_Class au
     * réveil). newFromBuilder() réhydrate ensuite un modèle normal, avec sa
     * relation media chargée à la demande (non mise en cache, requête légère).
     */
    public static function actuel(): self
    {
        $attributs = Cache::rememberForever(self::CACHE_KEY, function () {
            return self::query()->firstOrCreate(['id' => 1], [
                'nom' => 'Gérer Mon Stock',
            ])->getAttributes();
        });

        return (new self)->newFromBuilder($attributs);
    }

    public static function invaliderCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Logo par défaut (SVG généré, voir public/images/logo-defaut.svg) tant
     * qu'aucun logo personnalisé n'est configuré — jamais de lien mort.
     */
    public function logoUrl(): string
    {
        return $this->getFirstMediaUrl('logo') ?: asset('images/logo-defaut.svg');
    }

    public function devise(): BelongsTo
    {
        return $this->belongsTo(Devise::class);
    }

    protected static function booted(): void
    {
        static::saved(function () {
            self::invaliderCache();
            // La devise affichée partout (fonction montant()) dépend de ce
            // singleton — sans ça, changer la devise active dans Paramètres
            // resterait sans effet jusqu'à l'expiration (jamais, en pratique,
            // Cache::rememberForever) du cache de Devise::abreviationActuelle().
            Devise::invaliderCache();
        });
        static::deleted(fn () => self::invaliderCache());
    }
}
