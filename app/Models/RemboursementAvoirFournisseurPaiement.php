<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['remboursement_avoir_fournisseur_id', 'moyen_paiement_id', 'montant'])]
class RemboursementAvoirFournisseurPaiement extends Model
{
    const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'montant' => 'integer',
        ];
    }

    public function remboursementAvoirFournisseur(): BelongsTo
    {
        return $this->belongsTo(RemboursementAvoirFournisseur::class);
    }

    public function moyenPaiement(): BelongsTo
    {
        return $this->belongsTo(MoyenPaiement::class);
    }
}
