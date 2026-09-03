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
 * Réception fournisseur, immuable : toujours liée à une commande d'achat
 * précise, ligne par ligne. Contrairement à un bon de livraison, mouvemente
 * réellement le stock/CMP/dette fournisseur au moment où elle est créée
 * (voir ReceptionAchatService) — jamais annulable, une correction se fait
 * via un retour fournisseur (voir CLAUDE.md, section Retours).
 */
#[Fillable(['numero', 'commande_achat_id', 'motif', 'numero_facture_fournisseur', 'numero_bon_livraison_fournisseur', 'created_by'])]
class ReceptionAchat extends Model
{
    use HasFactory, LogsActivity;

    const UPDATED_AT = null;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable();
    }

    public function commandeAchat(): BelongsTo
    {
        return $this->belongsTo(CommandeAchat::class)->withTrashed();
    }

    public function auteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lignes(): HasMany
    {
        return $this->hasMany(LigneReceptionAchat::class);
    }

    public function paiements(): HasMany
    {
        return $this->hasMany(PaiementAchat::class);
    }

    /**
     * Suppose `lignes` chargée.
     */
    public function totalHt(): int
    {
        return $this->lignes->sum(fn (LigneReceptionAchat $ligne) => $ligne->montantHt());
    }

    public function totalTtc(): int
    {
        return $this->lignes->sum(fn (LigneReceptionAchat $ligne) => $ligne->montantTtc());
    }

    /**
     * Suppose `paiements` chargée.
     */
    public function montantRegle(): int
    {
        return $this->paiements->sum('montant');
    }

    public function resteDu(): int
    {
        return $this->totalTtc() - $this->montantRegle();
    }
}
