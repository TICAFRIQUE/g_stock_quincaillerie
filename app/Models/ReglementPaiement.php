<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['reglement_client_id', 'moyen_paiement_id', 'montant'])]
class ReglementPaiement extends Model
{
    const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'montant' => 'integer',
        ];
    }

    public function reglementClient(): BelongsTo
    {
        return $this->belongsTo(ReglementClient::class);
    }

    public function moyenPaiement(): BelongsTo
    {
        return $this->belongsTo(MoyenPaiement::class);
    }
}
