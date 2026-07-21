<?php

namespace App\Models;

use App\Enums\MouvementStockType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

#[Fillable([
    'produit_id', 'magasin_id', 'type', 'quantite',
    'reference_type', 'reference_id', 'motif', 'created_by',
])]
class MouvementStock extends Model
{
    use HasFactory, LogsActivity;

    const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'type' => MouvementStockType::class,
            'quantite' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable();
    }

    /**
     * withTrashed() : un mouvement de stock est un registre immuable, il
     * doit rester consultable même si le produit ou le magasin ont été
     * supprimés (soft delete) depuis.
     */
    public function produit(): BelongsTo
    {
        return $this->belongsTo(Produit::class)->withTrashed();
    }

    public function magasin(): BelongsTo
    {
        return $this->belongsTo(Magasin::class)->withTrashed();
    }

    public function auteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
