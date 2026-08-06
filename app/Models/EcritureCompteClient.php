<?php

namespace App\Models;

use App\Enums\EcritureCompteClientType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Registre immuable du compte d'un client : aucun UPDATE/DELETE applicatif.
 * Le solde du client est la somme de ses écritures (règle 12), jamais une
 * valeur stockée à part — même principe que mouvement_stocks pour le stock.
 */
#[Fillable(['client_id', 'type', 'montant', 'reference_type', 'reference_id', 'created_by'])]
class EcritureCompteClient extends Model
{
    const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'type' => EcritureCompteClientType::class,
            'montant' => 'integer',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
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
