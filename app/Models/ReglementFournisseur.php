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
 * Paiement (total ou partiel) d'une dette fournisseur, immuable comme
 * ReglementClient mais SANS lien avec une caisse : indépendant de toute
 * session, n'impacte jamais le tiroir (décision produit, voir CLAUDE.md).
 */
#[Fillable(['fournisseur_id', 'commande_achat_id', 'created_by', 'montant'])]
class ReglementFournisseur extends Model
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

    public function fournisseur(): BelongsTo
    {
        return $this->belongsTo(Fournisseur::class);
    }

    /**
     * Nullable : un règlement saisi via le bouton général de la fiche
     * fournisseur n'est imputé à aucune commande précise.
     */
    public function commandeAchat(): BelongsTo
    {
        return $this->belongsTo(CommandeAchat::class)->withTrashed();
    }

    public function auteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function paiements(): HasMany
    {
        return $this->hasMany(ReglementFournisseurPaiement::class);
    }
}
