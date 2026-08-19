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
    'montant_recu', 'monnaie_rendue', 'avoir_applique', 'motif_annulation', 'annulee_par',
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
            'avoir_applique' => 'integer',
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

    public function retours(): HasMany
    {
        return $this->hasMany(RetourVente::class);
    }

    public function paiements(): HasMany
    {
        return $this->hasMany(Paiement::class);
    }

    /**
     * Règlements ultérieurs explicitement imputés à cette vente (bouton
     * "Régler" depuis son détail ou depuis une ligne de l'historique du
     * compte client) — distincts des `paiements`, encaissés à la vente.
     */
    public function reglementsClient(): HasMany
    {
        return $this->hasMany(ReglementClient::class);
    }

    /**
     * Total réglé au titre de cette vente : paiements encaissés à la vente +
     * règlements ultérieurs imputés à cette vente. Suppose `paiements` et
     * `reglementsClient` chargées.
     */
    public function montantRegle(): int
    {
        return $this->paiements->sum('montant') + $this->reglementsClient->sum('montant');
    }

    /**
     * Reste dû par le client : net à payer moins le montant réglé (0 pour
     * une vente comptant). Suppose `paiements` et `reglementsClient` chargées.
     */
    public function soldeDu(): int
    {
        return $this->total_net - $this->montantRegle();
    }

    /**
     * Reste réellement dû : soldeDu() net de la part déjà couverte par un
     * avoir client au moment de la vente (avoir_applique, figé à la
     * création — voir VenteService::vendre()). Sans ça, une facture
     * intégralement compensée par un avoir préexistant continuait d'afficher
     * un "reste dû" alors que le compte du client était déjà à 0 (l'avoir
     * se déduit automatiquement de la dette posée, règle 12/20) — c'est
     * cette valeur qu'il faut utiliser partout où "reste dû" signifie une
     * vraie dette encore ouverte (ticket, bouton "Régler", export).
     */
    public function soldeDuReel(): int
    {
        return max(0, $this->soldeDu() - $this->avoir_applique);
    }
}
