<?php

namespace App\Services;

use App\Enums\MouvementStockType;
use App\Models\CommandeAchat;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

/**
 * Pas d'entité "Réception" séparée : valider une commande d'achat impacte le
 * stock directement, une ligne = un mouvement d'entrée + recalcul du CMP.
 */
class AchatService
{
    public function __construct(
        private readonly StockService $stockService,
        private readonly CompteFournisseurService $compteFournisseurService,
    ) {}

    /**
     * @param  array<int, array{moyen_paiement_id:int, montant:int}>  $paiements
     */
    public function valider(CommandeAchat $commandeAchat, User $auteur, array $paiements = []): CommandeAchat
    {
        if ($commandeAchat->statut !== 'brouillon') {
            throw new RuntimeException("La commande {$commandeAchat->numero} n'est pas au statut brouillon.");
        }

        return DB::transaction(function () use ($commandeAchat, $auteur, $paiements) {
            $commandeAchat->loadMissing('lignes.produit', 'lignes.uniteVente', 'lignes.magasinDestination', 'lignes.taxe');

            foreach ($commandeAchat->lignes as $ligne) {
                $this->stockService->enregistrerMouvement(
                    produit: $ligne->produit,
                    magasin: $ligne->magasinDestination,
                    quantite: $ligne->quantite_pieces,
                    type: MouvementStockType::Reception,
                    auteur: $auteur,
                    reference: $commandeAchat,
                    prixAchat: $ligne->prixAchatParPiece(),
                );
            }

            $totalTtc = $commandeAchat->totalTtc();
            $totalPaiements = array_sum(array_column($paiements, 'montant'));

            if ($totalPaiements > $totalTtc) {
                throw new InvalidArgumentException(
                    "Le total des paiements ({$totalPaiements}) ne peut pas dépasser le total TTC ({$totalTtc})."
                );
            }

            foreach ($paiements as $p) {
                $commandeAchat->paiements()->create([
                    'moyen_paiement_id' => $p['moyen_paiement_id'],
                    'montant' => $p['montant'],
                ]);
            }

            $resteDu = $totalTtc - $totalPaiements;
            if ($resteDu > 0) {
                $this->compteFournisseurService->crediterDette($commandeAchat->fournisseur, $resteDu, $commandeAchat, $auteur);
            }

            $commandeAchat->update([
                'statut' => 'validee',
                'valide_by' => $auteur->id,
                'valide_at' => now(),
            ]);

            return $commandeAchat->refresh();
        });
    }

    /**
     * Annule une commande validée : la commande n'est ni supprimée ni
     * modifiée dans son contenu (lignes, prix), elle est marquée annulée
     * (soft delete + motif + auteur) et un mouvement de stock inverse
     * (immuable, sortie) restitue exactement ce que la réception avait
     * ajouté. Échoue (et annule toute la transaction) si une partie du
     * stock reçu a déjà été consommée ailleurs (vente, transfert…) —
     * annulation tout ou rien, jamais partielle.
     */
    public function annuler(CommandeAchat $commandeAchat, User $auteur, string $motif): CommandeAchat
    {
        if ($commandeAchat->statut !== 'validee') {
            throw new RuntimeException("La commande {$commandeAchat->numero} n'est pas validée : utilisez la suppression pour un brouillon.");
        }

        return DB::transaction(function () use ($commandeAchat, $auteur, $motif) {
            $commandeAchat->loadMissing('lignes.produit', 'lignes.uniteVente', 'lignes.magasinDestination');

            foreach ($commandeAchat->lignes as $ligne) {
                $this->stockService->enregistrerMouvement(
                    produit: $ligne->produit,
                    magasin: $ligne->magasinDestination,
                    quantite: -$ligne->quantite_pieces,
                    type: MouvementStockType::Annulation,
                    auteur: $auteur,
                    reference: $commandeAchat,
                    motif: $motif,
                );
            }

            $commandeAchat->motif_annulation = $motif;
            $commandeAchat->annulee_par = $auteur->id;
            $commandeAchat->save();
            $commandeAchat->delete();

            return $commandeAchat;
        });
    }
}
