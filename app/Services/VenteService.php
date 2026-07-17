<?php

namespace App\Services;

use App\Enums\MouvementStockType;
use App\Exceptions\SessionNonOuverteException;
use App\Models\Caisse;
use App\Models\Produit;
use App\Models\SessionCaisse;
use App\Models\UniteVente;
use App\Models\User;
use App\Models\Vente;
use App\Support\Arrondi;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Vente atomique : stock (mouvements) + paiements + rattachement à la session,
 * dans une seule transaction. Tout ou rien (règle non négociable n°3).
 *
 * Remises : lignes d'abord, puis remise sur le sous-total. Chaque montant
 * résolu est stocké à côté du type/de la valeur saisie pour un ticket et des
 * rapports reproductibles.
 */
class VenteService
{
    public function __construct(private readonly StockService $stockService) {}

    /**
     * @param  array<int, array{produit_id:int, unite_vente_id?:?int, quantite:int, remise_type?:?string, remise_valeur?:?int}>  $lignes
     * @param  array<int, array{moyen_paiement_id:int, montant:int}>  $paiements
     */
    public function vendre(
        SessionCaisse $session,
        User $caissier,
        array $lignes,
        array $paiements,
        ?string $remiseTotaleType = null,
        ?int $remiseTotaleValeur = null,
    ): Vente {
        if (empty($lignes)) {
            throw new InvalidArgumentException('Une vente doit comporter au moins une ligne.');
        }

        $session = $session->fresh();
        if ($session->date_cloture !== null || $session->date_fermeture !== null) {
            throw new SessionNonOuverteException();
        }

        return DB::transaction(function () use ($session, $caissier, $lignes, $paiements, $remiseTotaleType, $remiseTotaleValeur) {
            $caisse = Caisse::whereKey($session->caisse_id)->lockForUpdate()->firstOrFail();
            $magasin = $caisse->magasin;

            $caisse->increment('sequence_ventes');
            $numero = sprintf('M%d-C%d-%06d', $magasin->id, $caisse->id, $caisse->sequence_ventes);

            [$lignesResolues, $sousTotal] = $this->resoudreLignes($lignes, $magasin);

            $remiseTotaleMontant = $this->resoudreRemise($remiseTotaleType, $remiseTotaleValeur, $sousTotal);
            $totalNet = $sousTotal - $remiseTotaleMontant;

            $totalPaiements = array_sum(array_column($paiements, 'montant'));
            if ($totalPaiements !== $totalNet) {
                throw new InvalidArgumentException(
                    "Le total des paiements ({$totalPaiements}) ne correspond pas au net à payer ({$totalNet})."
                );
            }

            $vente = Vente::create([
                'numero' => $numero,
                'magasin_id' => $magasin->id,
                'session_caisse_id' => $session->id,
                'caissier_id' => $caissier->id,
                'sous_total' => $sousTotal,
                'remise_totale_type' => $remiseTotaleType,
                'remise_totale_valeur' => $remiseTotaleValeur,
                'remise_totale_montant' => $remiseTotaleMontant,
                'total_net' => $totalNet,
            ]);

            foreach ($lignesResolues as $l) {
                $vente->lignes()->create([
                    'produit_id' => $l['produit']->id,
                    'unite_vente_id' => $l['unite_vente']?->id,
                    'quantite' => $l['quantite'],
                    'quantite_pieces' => $l['quantite_pieces'],
                    'prix_unitaire_applique' => $l['prix_unitaire_applique'],
                    'cout_applique' => $l['cout_applique'],
                    'sous_total_ligne' => $l['sous_total_ligne'],
                    'remise_ligne_type' => $l['remise_ligne_type'],
                    'remise_ligne_valeur' => $l['remise_ligne_valeur'],
                    'remise_ligne_montant' => $l['remise_ligne_montant'],
                    'total_ligne' => $l['total_ligne'],
                ]);

                // Lève StockInsuffisantException si le stock est insuffisant, ce qui
                // annule toute la transaction (vente, lignes, paiements compris).
                $this->stockService->enregistrerMouvement(
                    $l['produit'], $magasin, -$l['quantite_pieces'], MouvementStockType::Vente, $caissier, reference: $vente,
                );
            }

            foreach ($paiements as $p) {
                $vente->paiements()->create([
                    'moyen_paiement_id' => $p['moyen_paiement_id'],
                    'montant' => $p['montant'],
                ]);
            }

            return $vente->refresh();
        });
    }

    /**
     * @return array{0: array<int, array<string, mixed>>, 1: int}
     */
    private function resoudreLignes(array $lignes, $magasin): array
    {
        $resolues = [];
        $sousTotal = 0;

        foreach ($lignes as $ligne) {
            $produit = Produit::findOrFail($ligne['produit_id']);
            $uniteVente = ! empty($ligne['unite_vente_id'])
                ? UniteVente::findOrFail($ligne['unite_vente_id'])
                : null;

            $quantite = $ligne['quantite'];
            if ($quantite <= 0) {
                throw new InvalidArgumentException('La quantité d\'une ligne de vente doit être positive.');
            }

            $quantitePieces = $quantite * ($uniteVente->facteur ?? 1);
            $prixUnitaireApplique = $uniteVente ? $uniteVente->prix : $produit->prix_piece;
            $sousTotalLigne = $prixUnitaireApplique * $quantite;

            $remiseLigneMontant = $this->resoudreRemise(
                $ligne['remise_type'] ?? null,
                $ligne['remise_valeur'] ?? null,
                $sousTotalLigne,
            );

            $totalLigne = $sousTotalLigne - $remiseLigneMontant;
            $sousTotal += $totalLigne;

            $resolues[] = [
                'produit' => $produit,
                'unite_vente' => $uniteVente,
                'quantite' => $quantite,
                'quantite_pieces' => $quantitePieces,
                'prix_unitaire_applique' => $prixUnitaireApplique,
                'cout_applique' => $this->stockService->coutMoyenPondere($produit, $magasin),
                'sous_total_ligne' => $sousTotalLigne,
                'remise_ligne_type' => $ligne['remise_type'] ?? null,
                'remise_ligne_valeur' => $ligne['remise_valeur'] ?? null,
                'remise_ligne_montant' => $remiseLigneMontant,
                'total_ligne' => $totalLigne,
            ];
        }

        return [$resolues, $sousTotal];
    }

    private function resoudreRemise(?string $type, ?int $valeur, int $base): int
    {
        if ($type === null || $valeur === null) {
            return 0;
        }

        $montant = match ($type) {
            'montant' => $valeur,
            'pourcentage' => Arrondi::entier($base * $valeur / 100),
            default => throw new InvalidArgumentException("Type de remise inconnu : {$type}"),
        };

        // Ne jamais dépasser la base : pas de sous-total ou de net à payer négatif.
        return min($montant, $base);
    }
}
