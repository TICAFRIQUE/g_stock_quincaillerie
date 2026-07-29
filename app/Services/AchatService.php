<?php

namespace App\Services;

use App\Enums\MouvementStockType;
use App\Models\CommandeAchat;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Pas d'entité "Réception" séparée : valider une commande d'achat impacte le
 * stock directement, une ligne = un mouvement d'entrée + recalcul du CMP.
 */
class AchatService
{
    public function __construct(private readonly StockService $stockService) {}

    public function valider(CommandeAchat $commandeAchat, User $auteur): CommandeAchat
    {
        if ($commandeAchat->statut !== 'brouillon') {
            throw new RuntimeException("La commande {$commandeAchat->numero} n'est pas au statut brouillon.");
        }

        return DB::transaction(function () use ($commandeAchat, $auteur) {
            $commandeAchat->loadMissing('lignes.produit');

            foreach ($commandeAchat->lignes as $ligne) {
                $this->stockService->enregistrerMouvement(
                    produit: $ligne->produit,
                    magasin: $commandeAchat->magasin,
                    quantite: $ligne->quantite_pieces,
                    type: MouvementStockType::Reception,
                    auteur: $auteur,
                    reference: $commandeAchat,
                    prixAchat: $ligne->prixAchatParPiece(),
                );
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
            $commandeAchat->loadMissing('lignes.produit');

            foreach ($commandeAchat->lignes as $ligne) {
                $this->stockService->enregistrerMouvement(
                    produit: $ligne->produit,
                    magasin: $commandeAchat->magasin,
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
