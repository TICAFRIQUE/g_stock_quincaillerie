<?php

namespace App\Services;

use App\Enums\EcritureCompteFournisseurType;
use App\Enums\MouvementStockType;
use App\Models\CommandeAchat;
use App\Models\EcritureCompteFournisseur;
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

        // annuler() restitue la quantite_pieces ORIGINALE de chaque ligne :
        // si une partie a déjà été rendue au fournisseur via un retour, ce
        // stock a déjà été repris, une annulation totale le reprendrait une
        // seconde fois.
        if ($commandeAchat->retours()->exists()) {
            throw new RuntimeException("La commande {$commandeAchat->numero} a déjà fait l'objet d'un retour partiel : annulation totale impossible.");
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

            // Reverse la dette exacte posée par crediterDette() à la
            // validation (retrouvée via l'écriture elle-même plutôt que
            // recalculée, pour rester robuste même si le total TTC a bougé
            // depuis — ex. taxe désactivée). Rien à faire si l'achat avait
            // été réglé intégralement à la validation (aucune écriture posée).
            $montantDette = EcritureCompteFournisseur::where('reference_type', $commandeAchat->getMorphClass())
                ->where('reference_id', $commandeAchat->id)
                ->where('type', EcritureCompteFournisseurType::AchatCredit)
                ->value('montant');

            if ($montantDette > 0) {
                $this->compteFournisseurService->annulerDette($commandeAchat->fournisseur, $montantDette, $commandeAchat, $auteur);
            }

            $commandeAchat->motif_annulation = $motif;
            $commandeAchat->annulee_par = $auteur->id;
            $commandeAchat->save();
            $commandeAchat->delete();

            return $commandeAchat;
        });
    }
}
