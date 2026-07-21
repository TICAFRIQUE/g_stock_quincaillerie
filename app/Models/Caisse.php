<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

#[Fillable(['magasin_id', 'nom', 'sequence_ventes', 'actif'])]
class Caisse extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected function casts(): array
    {
        return [
            'sequence_ventes' => 'integer',
            'actif' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    /**
     * withTrashed() : évite un crash d'affichage si le magasin a été
     * supprimé (soft delete) alors que la caisse le référence encore.
     */
    public function magasin(): BelongsTo
    {
        return $this->belongsTo(Magasin::class)->withTrashed();
    }

    public function sessionCaisses(): HasMany
    {
        return $this->hasMany(SessionCaisse::class);
    }
}
