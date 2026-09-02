<?php

namespace App\Models;

use App\Models\Concerns\MetEnFormePhrase;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Référentiel des offres d'abonnement, géré depuis l'écran Gestion abonnement
 * (réservé Superadmin/développeur, voir User::estGestionnaireAbonnement()).
 * Soit un nombre de jours, soit illimité (jours alors nul) — voir
 * Abonnement::activer().
 */
#[Fillable(['nom', 'jours', 'illimite', 'prix', 'actif'])]
class FormuleAbonnement extends Model
{
    use HasFactory, LogsActivity, MetEnFormePhrase;

    protected function casts(): array
    {
        return [
            'jours' => 'integer',
            'illimite' => 'boolean',
            'prix' => 'integer',
            'actif' => 'boolean',
        ];
    }

    protected function nom(): Attribute
    {
        return Attribute::make(set: fn (?string $value) => static::casseEnPhrase($value));
    }

    public function activations(): HasMany
    {
        return $this->hasMany(AbonnementActivation::class, 'formule_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }
}
