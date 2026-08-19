<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Remboursement (total ou partiel) d'un avoir client, immuable comme un
 * règlement. session_caisse_id nullable : requis seulement si une partie
 * sort en espèces (voir RemboursementAvoirClientService, CLAUDE.md).
 */
#[Fillable(['client_id', 'session_caisse_id', 'created_by', 'montant'])]
class RemboursementAvoirClient extends Model
{
    use HasFactory, LogsActivity;

    const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'montant' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable();
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function sessionCaisse(): BelongsTo
    {
        return $this->belongsTo(SessionCaisse::class);
    }

    public function auteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function paiements(): HasMany
    {
        return $this->hasMany(RemboursementAvoirClientPaiement::class);
    }
}
