<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

#[Fillable(['magasin_id', 'statut', 'date', 'created_by', 'valide_by', 'valide_at'])]
class Inventaire extends Model
{
    use HasFactory, LogsActivity;

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'valide_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    /**
     * withTrashed() : un inventaire est un historique, il doit rester
     * affichable même si le magasin a été supprimé (soft delete) depuis.
     */
    public function magasin(): BelongsTo
    {
        return $this->belongsTo(Magasin::class)->withTrashed();
    }

    public function auteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function validateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'valide_by');
    }

    public function lignes(): HasMany
    {
        return $this->hasMany(LigneInventaire::class);
    }
}
