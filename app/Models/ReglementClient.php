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
 * Encaissement (total ou partiel) d'une dette client, immuable comme une
 * vente. Toujours rattaché à une session de caisse ouverte au moment de
 * l'encaissement (règle 14).
 */
#[Fillable(['client_id', 'session_caisse_id', 'caissier_id', 'montant'])]
class ReglementClient extends Model
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

    public function caissier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'caissier_id');
    }

    public function paiements(): HasMany
    {
        return $this->hasMany(ReglementPaiement::class);
    }
}
