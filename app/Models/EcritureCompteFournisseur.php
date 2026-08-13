<?php

namespace App\Models;

use App\Enums\EcritureCompteFournisseurType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Registre immuable du compte d'un fournisseur : aucun UPDATE/DELETE
 * applicatif. Le solde (ce qu'on doit au fournisseur) est la somme de ses
 * écritures — même principe que EcritureCompteClient pour les clients.
 */
#[Fillable(['fournisseur_id', 'type', 'montant', 'reference_type', 'reference_id', 'created_by'])]
class EcritureCompteFournisseur extends Model
{
    const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'type' => EcritureCompteFournisseurType::class,
            'montant' => 'integer',
        ];
    }

    public function fournisseur(): BelongsTo
    {
        return $this->belongsTo(Fournisseur::class);
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
