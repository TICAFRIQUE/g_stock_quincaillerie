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
    'numero', 'magasin_id', 'session_caisse_id', 'caissier_id', 'client_id', 'sous_total',
    'remise_totale_type', 'remise_totale_valeur', 'remise_totale_montant', 'total_net',
    'montant_recu', 'monnaie_rendue', 'motif_annulation', 'annulee_par',
])]
class Vente extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    const UPDATED_AT = null;

    /**
     * Une vente "supprimée" (deleted_at) représente une annulation, pas une
     * perte de données : le contenu (lignes, montants) n'est jamais modifié,
     * elle reste consultable (withTrashed) et exclue par défaut des
     * agrégats (CA, rapports) — voir VenteController::annuler().
     */
    protected function casts(): array
    {
        return [
            'sous_total' => 'integer',
            'remise_totale_valeur' => 'integer',
            'remise_totale_montant' => 'integer',
            'total_net' => 'integer',
            'montant_recu' => 'integer',
            'monnaie_rendue' => 'integer',
            'deleted_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable();
    }

    /**
     * withTrashed() : une vente est un historique immuable, elle doit
     * rester affichable (ticket, rapports) même si le magasin a été
     * supprimé (soft delete) depuis.
     */
    public function magasin(): BelongsTo
    {
        return $this->belongsTo(Magasin::class)->withTrashed();
    }

    public function sessionCaisse(): BelongsTo
    {
        return $this->belongsTo(SessionCaisse::class);
    }

    public function caissier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'caissier_id');
    }

    public function annulateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'annulee_par');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function lignes(): HasMany
    {
        return $this->hasMany(LigneVente::class);
    }

    public function paiements(): HasMany
    {
        return $this->hasMany(Paiement::class);
    }

    /**
     * Reste dû par le client : net à payer moins les paiements réellement
     * encaissés (0 pour une vente comptant). Suppose `paiements` chargée.
     */
    public function soldeDu(): int
    {
        return $this->total_net - $this->paiements->sum('montant');
    }
}
