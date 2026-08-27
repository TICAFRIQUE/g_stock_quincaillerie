<?php

namespace App\Models;

use App\Models\Concerns\MetEnFormePhrase;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

#[Fillable(['nom', 'code', 'type_client_id', 'telephone', 'adresse', 'limite_credit', 'actif'])]
class Client extends Model
{
    use HasFactory, LogsActivity, SoftDeletes, MetEnFormePhrase;

    protected function casts(): array
    {
        return [
            'limite_credit' => 'integer',
            'actif' => 'boolean',
        ];
    }

    protected function nom(): Attribute
    {
        return Attribute::make(set: fn (?string $value) => static::casseEnPhrase($value));
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable();
    }

    public function ventes(): HasMany
    {
        return $this->hasMany(Vente::class);
    }

    public function typeClient(): BelongsTo
    {
        return $this->belongsTo(TypeClient::class);
    }

    public function ecritures(): HasMany
    {
        return $this->hasMany(EcritureCompteClient::class);
    }

    public function reglements(): HasMany
    {
        return $this->hasMany(ReglementClient::class);
    }

    public function devis(): HasMany
    {
        return $this->hasMany(Devis::class);
    }

    /**
     * Solde dérivé, jamais stocké (règle 12) : somme des écritures du
     * compte. Positif = le client doit de l'argent.
     */
    public function solde(): int
    {
        return $this->ecritures()->sum('montant');
    }

    /**
     * Chiffre d'affaires total réalisé avec ce client (ventes non annulées).
     * KPI fiche client.
     */
    public function totalVentes(): int
    {
        return $this->ventes()->sum('total_net');
    }

    /**
     * Total effectivement encaissé auprès de ce client, toutes voies
     * confondues : paiements à la vente + règlements ultérieurs. Distinct de
     * solde() qui reflète la dette RESTANTE (après retours/annulations).
     */
    public function totalRegle(): int
    {
        $paiements = Paiement::whereHas('vente', fn ($q) => $q->where('client_id', $this->id))->sum('montant');

        return $paiements + $this->reglements()->sum('montant');
    }

    /**
     * Clients actifs avec une dette en cours, pour l'écran de règlement —
     * solde calculé en une seule requête groupée (withSum + having), jamais
     * client par client (évite un N+1 sur potentiellement tout le fichier
     * client).
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, self>
     */
    public static function actifsAvecDette(): \Illuminate\Database\Eloquent\Collection
    {
        return static::query()
            ->where('actif', true)
            ->withSum('ecritures as solde', 'montant')
            ->having('solde', '>', 0)
            ->orderBy('nom')
            ->get();
    }
}
