<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

#[Fillable([
    'produit_id', 'quantite', 'magasin_source_id', 'magasin_destination_id', 'created_by',
])]
class Transfert extends Model
{
    use HasFactory, LogsActivity;

    const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'quantite' => 'decimal:3',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable();
    }

    /**
     * withTrashed() : un transfert est un registre immuable, il doit
     * rester consultable même si le produit ou les magasins ont été
     * supprimés (soft delete) depuis.
     */
    public function produit(): BelongsTo
    {
        return $this->belongsTo(Produit::class)->withTrashed();
    }

    public function magasinSource(): BelongsTo
    {
        return $this->belongsTo(Magasin::class, 'magasin_source_id')->withTrashed();
    }

    public function magasinDestination(): BelongsTo
    {
        return $this->belongsTo(Magasin::class, 'magasin_destination_id')->withTrashed();
    }

    public function auteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function mouvementStocks(): MorphMany
    {
        return $this->morphMany(MouvementStock::class, 'reference');
    }
}
