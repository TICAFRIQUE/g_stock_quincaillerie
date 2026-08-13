<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

#[Fillable([
    'numero', 'fournisseur_id', 'statut', 'date_commande',
    'created_by', 'valide_by', 'valide_at', 'motif_annulation', 'annulee_par',
])]
class CommandeAchat extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected function casts(): array
    {
        return [
            'date_commande' => 'date',
            'valide_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    /**
     * withTrashed() : une commande d'achat est un historique, elle doit
     * rester affichable même si le fournisseur a été supprimé (soft delete)
     * depuis.
     */
    public function fournisseur(): BelongsTo
    {
        return $this->belongsTo(Fournisseur::class)->withTrashed();
    }

    public function auteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function validateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'valide_by');
    }

    public function annulateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'annulee_par');
    }

    public function lignes(): HasMany
    {
        return $this->hasMany(LigneCommandeAchat::class);
    }

    /**
     * Suppose `lignes` chargée (loadMissing en amont, voir
     * CommandeAchatController::show()).
     */
    public function totalHt(): int
    {
        return $this->lignes->sum(fn (LigneCommandeAchat $ligne) => $ligne->montantHt());
    }

    public function totalTtc(): int
    {
        return $this->lignes->sum(fn (LigneCommandeAchat $ligne) => $ligne->montantTtc());
    }

    public function totalTaxes(): int
    {
        return $this->totalTtc() - $this->totalHt();
    }

    public function paiements(): HasMany
    {
        return $this->hasMany(PaiementAchat::class);
    }

    /**
     * Reste dû au fournisseur : total TTC moins les paiements réellement
     * versés à la validation (0 pour un achat payé comptant). Suppose
     * `lignes` et `paiements` chargées.
     */
    public function resteDu(): int
    {
        return $this->totalTtc() - $this->paiements->sum('montant');
    }
}
