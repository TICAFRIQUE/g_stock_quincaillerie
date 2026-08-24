<?php

namespace App\Models;

use App\Enums\EcritureCompteTresorerieType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Registre immuable du compte de trésorerie : aucun UPDATE/DELETE
 * applicatif. Le solde est la somme de ses écritures — même principe que
 * EcritureCompteFournisseur.
 */
#[Fillable(['compte_tresorerie_id', 'type', 'montant', 'motif', 'reference_type', 'reference_id', 'created_by'])]
class EcritureCompteTresorerie extends Model
{
    const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'type' => EcritureCompteTresorerieType::class,
            'montant' => 'integer',
        ];
    }

    public function compteTresorerie(): BelongsTo
    {
        return $this->belongsTo(CompteTresorerie::class);
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
