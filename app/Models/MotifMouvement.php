<?php

namespace App\Models;

use App\Models\Concerns\MetEnFormePhrase;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Référentiel des motifs proposés pour un mouvement d'entrée/sortie —
 * caisse de caissier (MouvementCaisse) ou trésorerie (EcritureCompteTresorerie).
 * N'est jamais une contrainte stricte : les deux modèles gardent leur propre
 * colonne `motif` en texte libre (règle 19 — "motif (libre, obligatoire)"),
 * ce référentiel ne fait que suggérer/harmoniser les libellés déjà utilisés.
 */
#[Fillable(['nom', 'actif'])]
class MotifMouvement extends Model
{
    use HasFactory, LogsActivity, SoftDeletes, MetEnFormePhrase;

    protected function casts(): array
    {
        return [
            'actif' => 'boolean',
        ];
    }

    protected function nom(): Attribute
    {
        return Attribute::make(set: fn (?string $value) => static::casseEnPhrase($value));
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }
}
