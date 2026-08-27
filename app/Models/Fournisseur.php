<?php

namespace App\Models;

use App\Models\Concerns\MetEnFormePhrase;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

#[Fillable(['nom', 'code', 'telephone', 'email', 'adresse', 'actif'])]
class Fournisseur extends Model
{
    use HasFactory, LogsActivity, SoftDeletes, MetEnFormePhrase;

    protected function casts(): array
    {
        return [
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

    public function commandeAchats(): HasMany
    {
        return $this->hasMany(CommandeAchat::class);
    }

    public function ecritures(): HasMany
    {
        return $this->hasMany(EcritureCompteFournisseur::class);
    }

    public function reglements(): HasMany
    {
        return $this->hasMany(ReglementFournisseur::class);
    }

    /**
     * Solde dérivé, jamais stocké (même principe que Client::solde()) :
     * somme des écritures du compte. Positif = on doit de l'argent au
     * fournisseur.
     */
    public function solde(): int
    {
        return $this->ecritures()->sum('montant');
    }

    /**
     * Total des achats passés auprès de ce fournisseur (commandes validées
     * non annulées, TTC). KPI fiche fournisseur. Suppose que le total TTC de
     * chaque ligne peut être sommé en SQL : prix_achat HT × quantité ×
     * (1 + taux taxe / 100) n'est pas trivial à exprimer en SQL brut à cause
     * de l'arrondi applicatif (voir Arrondi::entier) — calculé ligne par
     * ligne en PHP via LigneCommandeAchat::montantTtc() plutôt qu'une requête
     * agrégée, sur un nombre de lignes qui reste raisonnable par fournisseur.
     */
    public function totalAchats(): int
    {
        return LigneCommandeAchat::whereHas(
            'commandeAchat',
            fn ($q) => $q->where('fournisseur_id', $this->id)->where('statut', 'validee')
        )->with('taxe')->get()->sum(fn (LigneCommandeAchat $l) => $l->montantTtc());
    }

    public function nombreAchats(): int
    {
        return $this->commandeAchats()->where('statut', 'validee')->count();
    }

    /**
     * Total effectivement versé à ce fournisseur, toutes voies confondues :
     * paiements à la validation + règlements ultérieurs. Distinct de
     * solde() qui reflète la dette RESTANTE (après retours/annulations).
     */
    public function totalRegle(): int
    {
        $paiements = PaiementAchat::whereHas(
            'commandeAchat',
            fn ($q) => $q->where('fournisseur_id', $this->id)
        )->sum('montant');

        return $paiements + $this->reglements()->sum('montant');
    }

    /**
     * Fournisseurs actifs avec une dette en cours, pour l'écran de
     * règlement — solde calculé en une seule requête groupée, jamais
     * fournisseur par fournisseur (voir Client::actifsAvecDette()).
     *
     * @return Collection<int, self>
     */
    public static function actifsAvecDette(): Collection
    {
        return static::query()
            ->where('actif', true)
            ->withSum('ecritures as solde', 'montant')
            ->having('solde', '>', 0)
            ->orderBy('nom')
            ->get();
    }
}
