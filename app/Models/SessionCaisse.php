<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

#[Fillable([
    'caisse_id', 'caissier_id', 'fond_de_caisse', 'date_ouverture',
    'date_cloture', 'date_fermeture', 'total_ventes_especes',
    'montant_compte', 'ecart', 'cloture_by', 'alerte_ouverture_envoyee_at',
])]
class SessionCaisse extends Model
{
    use HasFactory, LogsActivity;

    protected function casts(): array
    {
        return [
            'fond_de_caisse' => 'integer',
            'date_ouverture' => 'datetime',
            'date_cloture' => 'datetime',
            'date_fermeture' => 'datetime',
            'total_ventes_especes' => 'integer',
            'montant_compte' => 'integer',
            'ecart' => 'integer',
            'alerte_ouverture_envoyee_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    public function caisse(): BelongsTo
    {
        return $this->belongsTo(Caisse::class);
    }

    public function caissier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'caissier_id');
    }

    public function clotureePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cloture_by');
    }

    public function ventes(): HasMany
    {
        return $this->hasMany(Vente::class);
    }

    public function venteEnAttentes(): HasMany
    {
        return $this->hasMany(VenteEnAttente::class);
    }
}
