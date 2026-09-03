<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['commande_achat_id', 'reception_achat_id', 'moyen_paiement_id', 'montant'])]
class PaiementAchat extends Model
{
    const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'montant' => 'integer',
        ];
    }

    public function commandeAchat(): BelongsTo
    {
        return $this->belongsTo(CommandeAchat::class);
    }

    /**
     * null pour un paiement saisi à la validation (ancien modèle) — voir
     * migration add_reception_achat_id_to_paiement_achats_table.
     */
    public function receptionAchat(): BelongsTo
    {
        return $this->belongsTo(ReceptionAchat::class);
    }

    public function moyenPaiement(): BelongsTo
    {
        return $this->belongsTo(MoyenPaiement::class);
    }
}
