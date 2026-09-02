<?php

namespace App\Services;

use App\Enums\MouvementStockType;
use App\Exceptions\QuantiteRetourInvalideException;
use App\Models\CommandeAchat;
use App\Models\LigneRetourAchat;
use App\Models\RetourAchat;
use App\Models\User;
use App\Support\Arrondi;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Retour fournisseur, symétrique de RetourVenteService : toujours lié à une
 * commande d'achat validée, ligne par ligne. Indépendant de la caisse, comme
 * ReglementFournisseur (voir CLAUDE.md).
 */
class RetourAchatService
{
    public function __construct(
        private readonly StockService $stockService,
        private readonly CompteFournisseurService $compteFournisseurService,
    ) {}

    /**
     * @param  array<int, array{ligne_commande_achat_id:int, quantite_pieces:int|float}>  $lignes
     */
    public function retourner(CommandeAchat $commandeAchat, array $lignes, User $auteur, ?string $motif = null): RetourAchat
    {
        if ($commandeAchat->trashed()) {
            throw new InvalidArgumentException("Impossible d'enregistrer un retour sur une commande annulée.");
        }

        if ($commandeAchat->statut !== 'validee') {
            throw new InvalidArgumentException(
                "Seule une commande validée a mouvementé du stock : rien à retourner sur un brouillon."
            );
        }

        $lignes = array_values(array_filter($lignes, fn (array $l) => ($l['quantite_pieces'] ?? 0) > 0));

        if (empty($lignes)) {
            throw new InvalidArgumentException('Un retour doit comporter au moins une ligne avec une quantité positive.');
        }

        $commandeAchat->loadMissing('lignes.produit', 'lignes.magasinDestination', 'lignes.taxe', 'fournisseur');

        $dejaRetourne = LigneRetourAchat::whereIn('ligne_commande_achat_id', $commandeAchat->lignes->pluck('id'))
            ->selectRaw('ligne_commande_achat_id, SUM(quantite_pieces) as total')
            ->groupBy('ligne_commande_achat_id')
            ->pluck('total', 'ligne_commande_achat_id');

        return DB::transaction(function () use ($commandeAchat, $lignes, $auteur, $motif, $dejaRetourne) {
            $retour = RetourAchat::create([
                'numero' => $this->genererNumero(),
                'commande_achat_id' => $commandeAchat->id,
                'fournisseur_id' => $commandeAchat->fournisseur_id,
                'motif' => $motif,
                'montant_total' => 0,
                'created_by' => $auteur->id,
            ]);

            $montantTotal = 0;

            foreach ($lignes as $demande) {
                $ligne = $commandeAchat->lignes->firstWhere('id', $demande['ligne_commande_achat_id']);

                if (! $ligne) {
                    throw new InvalidArgumentException("Ligne de commande introuvable sur ce bon d'achat.");
                }

                $quantiteAvant = (float) ($dejaRetourne[$ligne->id] ?? 0);
                $quantiteRestante = (float) $ligne->quantite_pieces - $quantiteAvant;
                $quantiteRetour = (float) $demande['quantite_pieces'];

                if ($quantiteRetour > $quantiteRestante) {
                    throw new QuantiteRetourInvalideException($ligne, $quantiteRetour, $quantiteRestante);
                }

                $montantLigne = $this->montantTelescope($ligne->montantTtc(), $ligne->quantite_pieces, $quantiteAvant, $quantiteRetour);

                $retour->lignes()->create([
                    'ligne_commande_achat_id' => $ligne->id,
                    'produit_id' => $ligne->produit_id,
                    'magasin_id' => $ligne->magasin_destination_id,
                    'quantite_pieces' => $quantiteRetour,
                    'montant' => $montantLigne,
                ]);

                // Sortie de stock : peut échouer (StockInsuffisantException)
                // si une partie a déjà été revendue/transférée ailleurs.
                $this->stockService->enregistrerMouvement(
                    produit: $ligne->produit,
                    magasin: $ligne->magasinDestination,
                    quantite: -$quantiteRetour,
                    type: MouvementStockType::RetourFournisseur,
                    auteur: $auteur,
                    reference: $retour,
                    motif: $motif,
                );

                $montantTotal += $montantLigne;
                $dejaRetourne[$ligne->id] = $quantiteAvant + $quantiteRetour;
            }

            $retour->update(['montant_total' => $montantTotal]);

            if ($montantTotal > 0) {
                $this->compteFournisseurService->crediterRetour($commandeAchat->fournisseur, $montantTotal, $retour, $auteur);
            }

            return $retour->fresh('lignes');
        });
    }

    private function montantTelescope(int $totalLigne, int|float $quantitePiecesTotal, int|float $quantiteAvant, int|float $quantiteRetour): int
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
            $numero = 'RF-'.str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
        } while (RetourAchat::where('numero', $numero)->exists());

        return $numero;
    }
}
