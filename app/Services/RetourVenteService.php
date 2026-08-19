<?php

namespace App\Services;

use App\Enums\MouvementStockType;
use App\Exceptions\QuantiteRetourInvalideException;
use App\Models\LigneRetourVente;
use App\Models\RetourVente;
use App\Models\User;
use App\Models\Vente;
use App\Support\Arrondi;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Retour client : toujours lié à une vente précise, ligne par ligne, jamais
 * un avoir libre (règle produit, voir CLAUDE.md section Retours). Ne
 * mouvemente jamais la caisse — l'avoir est crédité sur le compte client via
 * CompteClientService::crediterRetour(), qui autorise un solde négatif.
 */
class RetourVenteService
{
    public function __construct(
        private readonly StockService $stockService,
        private readonly CompteClientService $compteClientService,
    ) {}

    /**
     * @param  array<int, array{ligne_vente_id:int, quantite_pieces:int}>  $lignes
     */
    public function retourner(Vente $vente, array $lignes, User $auteur, ?string $motif = null): RetourVente
    {
        if ($vente->trashed()) {
            throw new InvalidArgumentException("Impossible d'enregistrer un retour sur une vente annulée.");
        }

        if ($vente->client_id === null) {
            throw new InvalidArgumentException(
                "Cette vente n'a pas de client identifié : impossible d'enregistrer un retour (aucun compte à créditer)."
            );
        }

        $lignes = array_values(array_filter($lignes, fn (array $l) => ($l['quantite_pieces'] ?? 0) > 0));

        if (empty($lignes)) {
            throw new InvalidArgumentException('Un retour doit comporter au moins une ligne avec une quantité positive.');
        }

        $vente->loadMissing('lignes.produit', 'lignes.magasinSource', 'client');

        // Une seule requête groupée pour les quantités déjà retournées de
        // toutes les lignes de la vente (zéro N+1 — règle Performance).
        $dejaRetourne = LigneRetourVente::whereIn('ligne_vente_id', $vente->lignes->pluck('id'))
            ->selectRaw('ligne_vente_id, SUM(quantite_pieces) as total')
            ->groupBy('ligne_vente_id')
            ->pluck('total', 'ligne_vente_id');

        return DB::transaction(function () use ($vente, $lignes, $auteur, $motif, $dejaRetourne) {
            $retour = RetourVente::create([
                'numero' => $this->genererNumero(),
                'vente_id' => $vente->id,
                'client_id' => $vente->client_id,
                'motif' => $motif,
                'montant_total' => 0,
                'created_by' => $auteur->id,
            ]);

            $montantTotal = 0;

            foreach ($lignes as $demande) {
                $ligneVente = $vente->lignes->firstWhere('id', $demande['ligne_vente_id']);

                if (! $ligneVente) {
                    throw new InvalidArgumentException('Ligne de vente introuvable sur cette facture.');
                }

                $quantiteAvant = (int) ($dejaRetourne[$ligneVente->id] ?? 0);
                $quantiteRestante = $ligneVente->quantite_pieces - $quantiteAvant;
                $quantiteRetour = (int) $demande['quantite_pieces'];

                if ($quantiteRetour > $quantiteRestante) {
                    throw new QuantiteRetourInvalideException($ligneVente, $quantiteRetour, $quantiteRestante);
                }

                $montantLigne = $this->montantTelescope($ligneVente->total_ligne, $ligneVente->quantite_pieces, $quantiteAvant, $quantiteRetour);

                $retour->lignes()->create([
                    'ligne_vente_id' => $ligneVente->id,
                    'produit_id' => $ligneVente->produit_id,
                    'magasin_id' => $ligneVente->magasin_source_id,
                    'quantite_pieces' => $quantiteRetour,
                    'montant' => $montantLigne,
                    'cout_applique' => $ligneVente->cout_applique,
                ]);

                $this->stockService->enregistrerMouvement(
                    produit: $ligneVente->produit,
                    magasin: $ligneVente->magasinSource,
                    quantite: $quantiteRetour,
                    type: MouvementStockType::RetourClient,
                    auteur: $auteur,
                    reference: $retour,
                    motif: $motif,
                );

                $montantTotal += $montantLigne;
                // Pour rester cohérent si le même formulaire référence deux
                // fois la même ligne (rejeu improbable mais pas impossible).
                $dejaRetourne[$ligneVente->id] = $quantiteAvant + $quantiteRetour;
            }

            $retour->update(['montant_total' => $montantTotal]);

            if ($montantTotal > 0) {
                $this->compteClientService->crediterRetour($vente->client, $montantTotal, $retour, $auteur);
            }

            return $retour->fresh('lignes');
        });
    }

    /**
     * Répartition proportionnelle du montant de ligne, calculée sur le cumulé
     * (pas incrément par incrément) pour que la somme de plusieurs retours
     * partiels converge exactement vers total_ligne sans dérive d'arrondi.
     */
    private function montantTelescope(int $totalLigne, int $quantitePiecesTotal, int $quantiteAvant, int $quantiteRetour): int
    {
        if ($quantitePiecesTotal <= 0) {
            return 0;
        }

        $cumuleApres = Arrondi::entier($totalLigne * ($quantiteAvant + $quantiteRetour) / $quantitePiecesTotal);
        $cumuleAvant = Arrondi::entier($totalLigne * $quantiteAvant / $quantitePiecesTotal);

        return $cumuleApres - $cumuleAvant;
    }

    private function genererNumero(): string
    {
        do {
            $numero = 'RC-'.str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
        } while (RetourVente::where('numero', $numero)->exists());

        return $numero;
    }
}
