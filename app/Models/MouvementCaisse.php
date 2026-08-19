<?php

namespace App\Models;

use App\Enums\MouvementCaisseType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Mouvement de caisse manuel (entrée/sortie de tiroir), immuable — même
 * principe que MouvementStock pour le stock : aucun UPDATE/DELETE
 * applicatif, correction = mouvement inverse.
 */
#[Fillable(['session_caisse_id', 'type', 'montant', 'motif', 'reference_type', 'reference_id', 'created_by'])]
class MouvementCaisse extends Model
{
    const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'type' => MouvementCaisseType::class,
            'montant' => 'integer',
        ];
    }

    public function sessionCaisse(): BelongsTo
    {
        return $this->belongsTo(SessionCaisse::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function auteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
