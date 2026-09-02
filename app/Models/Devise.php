<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Référentiel d'affichage uniquement — aucune conversion, aucun taux, aucun
 * changement de précision des montants (toujours des entiers, francs).
 * Seule l'abréviation choisie dans Paramètres change ce qui s'affiche
 * (voir abreviationActuelle(), fonction montant() dans app/helpers.php).
 *
 * Jamais MetEnFormePhrase ici : "FCFA"/"USD" en casse phrase deviendrait
 * "Fcfa"/"Usd", ce qui casse le sigle — nom et abréviation restent tels que
 * saisis par l'administrateur.
 */
#[Fillable(['nom', 'abreviation', 'actif'])]
class Devise extends Model
{
    use LogsActivity;

    public const CACHE_KEY = 'devise.abreviation_actuelle';

    protected function casts(): array
    {
        return [
            'actif' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    /**
     * Abréviation de la devise actuellement choisie dans Paramètres (voir
     * Parametre::devise()), "FCFA" par défaut si aucune n'est configurée.
     * Mise en cache : lue à chaque montant affiché, ne doit jamais être une
     * requête SQL à chaque fois.
     */
    public static function abreviationActuelle(): string
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            return Parametre::actuel()->devise?->abreviation ?? 'FCFA';
        });
    }

    public static function invaliderCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    protected static function booted(): void
    {
        static::saved(fn () => self::invaliderCache());
        static::deleted(fn () => self::invaliderCache());
    }
}
