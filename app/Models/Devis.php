<?php

namespace App\Models;

use App\Enums\DevisStatut;
use App\Support\Remise;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Document commercial pré-vente : ne mouvemente ni le stock ni la caisse
 * (règle 15). Montants toujours indicatifs (jamais figés), transformable en
 * Vente tant qu'il n'est pas expiré/refusé/déjà transformé.
 */
#[Fillable(['numero', 'magasin_id', 'client_id', 'created_by', 'statut', 'date_validite', 'vente_id', 'transforme_at'])]
class Devis extends Model
{
    use HasFactory, LogsActivity;

    protected function casts(): array
    {
        return [
            'statut' => DevisStatut::class,
            'date_validite' => 'date',
            'transforme_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    public function magasin(): BelongsTo
    {
        return $this->belongsTo(Magasin::class)->withTrashed();
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class)->withTrashed();
    }

    public function auteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function vente(): BelongsTo
    {
        return $this->belongsTo(Vente::class);
    }

    public function lignes(): HasMany
    {
        return $this->hasMany(LigneDevis::class);
    }

    /**
     * L'expiration n'est jamais stockée : un devis encore brouillon dont la
     * date de validité est dépassée est expiré, calculé à la volée (règle
     * 15, comme le stock jamais figé).
     */
    public function statutEffectif(): DevisStatut
    {
        if ($this->statut === DevisStatut::Brouillon && $this->date_validite->isPast()) {
            return DevisStatut::Expire;
        }

        return $this->statut;
    }

    public function peutEtreModifie(): bool
    {
        return $this->statut === DevisStatut::Brouillon;
    }

    /**
     * Pas d'étape « envoyé »/« accepté » séparée pour le moment : un devis
     * brouillon non expiré se transforme directement en vente, ce qui vaut
     * acceptation (voir CLAUDE.md).
     */
    public function peutEtreTransforme(): bool
    {
        return $this->statutEffectif() === DevisStatut::Brouillon;
    }

    public function peutEtreAnnule(): bool
    {
        return $this->statut !== DevisStatut::Transforme;
    }

    /**
     * Montants indicatifs, recalculés à partir du prix courant du catalogue
     * (jamais figés, règle 15) — suppose lignes.produit et lignes.uniteVente
     * chargées.
     *
     * @return array{sous_total:int, total_net:int}
     */
    public function calculerMontants(): array
    {
        $sousTotal = 0;

        foreach ($this->lignes as $ligne) {
            $prixUnitaire = $ligne->uniteVente?->prix ?? $ligne->produit->prix_piece;
            $sousTotalLigne = $prixUnitaire * $ligne->quantite;
            $remiseLigne = Remise::resoudre($ligne->remise_type, $ligne->remise_valeur, $sousTotalLigne);
            $sousTotal += $sousTotalLigne - $remiseLigne;
        }

        return ['sous_total' => $sousTotal, 'total_net' => $sousTotal];
    }

    /**
     * Un devis ne réserve jamais de stock (règle 15) : le stock disponible
     * au magasin du devis peut donc être devenu insuffisant entre sa
     * création et une tentative de transformation. Vérifié ici pour
     * avertir en amont plutôt que de laisser échouer la vente après coup
     * (même logique que le grisage sur l'écran de vente, voir CLAUDE.md) —
     * suppose lignes.produit et lignes.uniteVente chargées.
     *
     * @return Collection<int, array{ligne: LigneDevis, demande: int, disponible: int}>
     */
    public function lignesEnRuptureDeStock(): Collection
    {
        $stocksParProduit = Stock::query()
            ->where('magasin_id', $this->magasin_id)
            ->whereIn('produit_id', $this->lignes->pluck('produit_id'))
            ->pluck('quantite', 'produit_id');

        return $this->lignes
            ->map(function (LigneDevis $ligne) use ($stocksParProduit) {
                $demande = $ligne->quantite * ($ligne->uniteVente->facteur ?? 1);
                $disponible = $stocksParProduit[$ligne->produit_id] ?? 0;

                return ['ligne' => $ligne, 'demande' => $demande, 'disponible' => $disponible];
            })
            ->filter(fn (array $etat) => $etat['demande'] > $etat['disponible'])
            ->values();
    }
}
