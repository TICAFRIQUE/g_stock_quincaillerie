<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registre immuable des activations d'abonnement : aucun UPDATE/DELETE
 * applicatif — une correction se fait par une nouvelle activation, jamais en
 * modifiant une ligne existante (même principe que EcritureCompteClient).
 * La situation courante de l'abonnement (voir Abonnement::class) est celle
 * de la plus récente ligne, pas un cumul de toutes les lignes.
 */
#[Fillable(['formule_id', 'montant', 'jours', 'illimite', 'jours_restants_reportes', 'date_debut', 'date_fin', 'note', 'created_by'])]
class AbonnementActivation extends Model
{
    const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'montant' => 'integer',
            'jours' => 'integer',
            'illimite' => 'boolean',
            'jours_restants_reportes' => 'integer',
            'date_debut' => 'date',
            'date_fin' => 'date',
        ];
    }

    public function formule(): BelongsTo
    {
        return $this->belongsTo(FormuleAbonnement::class, 'formule_id');
    }

    public function auteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
