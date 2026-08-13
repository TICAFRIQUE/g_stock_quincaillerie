<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['reglement_fournisseur_id', 'moyen_paiement_id', 'montant'])]
class ReglementFournisseurPaiement extends Model
{
    const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'montant' => 'integer',
        ];
    }

    public function reglementFournisseur(): BelongsTo
    {
        return $this->belongsTo(ReglementFournisseur::class);
    }

    public function moyenPaiement(): BelongsTo
    {
        return $this->belongsTo(MoyenPaiement::class);
    }
}
