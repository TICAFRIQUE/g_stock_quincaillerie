<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Compte de trésorerie de l'entreprise (Caisse Générale ou compte bancaire/
 * autre) — indépendant des caisses de vente des caissiers (voir CLAUDE.md,
 * Trésorerie). Le type `caisse_generale` est un singleton créé par
 * CompteTresorerieSeeder, jamais créable/supprimable depuis l'UI.
 */
#[Fillable(['nom', 'type', 'actif'])]
class CompteTresorerie extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

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

    public function ecritures(): HasMany
    {
        return $this->hasMany(EcritureCompteTresorerie::class);
    }

    public function solde(): int
    {
        return $this->ecritures()->sum('montant');
    }
}
