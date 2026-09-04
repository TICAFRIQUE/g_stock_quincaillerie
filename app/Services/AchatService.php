<?php

namespace App\Services;

use App\Enums\EcritureCompteFournisseurType;
use App\Enums\MouvementStockType;
use App\Models\CommandeAchat;
use App\Models\EcritureCompteFournisseur;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Valider une commande d'achat ne fait plus que la confirmer (envoyée au
 * fournisseur, ouverte aux réceptions) — le stock, le CMP et la dette
 * fournisseur ne bougent plus qu'à chaque réception (voir
 * ReceptionAchatService), pas à la validation. Une commande créée avant ce
 * changement a, elle, déjà tout reçu d'un coup à sa validation (ancien
 * modèle) : son affichage/comportement reste inchangé, distingué sans aucun
 * flag stocké (voir CommandeAchat::totalTtcReel(), annuler() ci-dessous).
 */
class AchatService
{
    public function __construct(
        private readonly StockService $stockService,
        private readonly CompteFournisseurService $compteFournisseurService,
    ) {}

    public function valider(CommandeAchat $commandeAchat, User $auteur): CommandeAchat
    {
        if ($commandeAchat->statut !== 'brouillon') {
            throw new RuntimeException("La commande {$commandeAchat->numero} n'est pas au statut brouillon.");
        }

        $commandeAchat->update([
            'statut' => 'validee',
            'valide_by' => $auteur->id,
            'valide_at' => now(),
        ]);

        return $commandeAchat->refresh();
    }

    /**
     * Annule une commande validée. Deux cas, distingués sans flag stocké :
     * - Nouveau modèle avec au moins une réception : annulation totale
     *   bloquée (les réceptions sont immuables, un impact stock/dette réel a
     *   déjà eu lieu) — corriger via un retour fournisseur.
     * - Nouveau modèle jamais réceptionné (validée mais aucune réception) :
     *   valider() n'a rien posé (ni stock ni dette) → rien à réconcilier,
     *   simple soft-delete + motif.
     * - Ancien modèle (mouvement de réception posé directement à la
     *   validation, avant ce changement) : comportement intégral inchangé —
     *   un mouvement de stock inverse restitue exactement ce que la
     *   réception avait ajouté, et la dette posée à la validation est
     *   reversée. Échoue (et annule toute la transaction) si une partie du
     *   stock reçu a déjà été consommée ailleurs (vente, transfert…) —
     *   annulation tout ou rien, jamais partielle.
     */
    public function annuler(CommandeAchat $commandeAchat, User $auteur, string $motif): CommandeAchat
    {
        if ($commandeAchat->statut !== 'validee') {
            throw new RuntimeException("La commande {$commandeAchat->numero} n'est pas validée : utilisez la suppression pour un brouillon.");
        }

        if ($commandeAchat->receptions()->exists()) {
            throw new RuntimeException("La commande {$commandeAchat->numero} a déjà été réceptionnée : corrigez via un retour fournisseur, pas une annulation.");
        }

        // annuler() restitue la quantite_pieces ORIGINALE de chaque ligne :
        // si une partie a déjà été rendue au fournisseur via un retour, ce
        // stock a déjà été repris, une annulation totale le reprendrait une
        // seconde fois. Ne concerne que l'ancien modèle (un retour suppose
        // une réception préalable — voir RetourAchatService).
        if ($commandeAchat->retours()->exists()) {
            throw new RuntimeException("La commande {$commandeAchat->numero} a déjà fait l'objet d'un retour partiel : annulation totale impossible.");
        }

        $estLegacyMouvementee = $commandeAchat->aDesMouvementsStockDirects();

        return DB::transaction(function () use ($commandeAchat, $auteur, $motif, $estLegacyMouvementee) {
            if ($estLegacyMouvementee) {
                $commandeAchat->loadMissing('lignes.produit', 'lignes.uniteVente', 'lignes.magasinDestination');

                foreach ($commandeAchat->lignes as $ligne) {
                    $this->stockService->enregistrerMouvement(
                        produit: $ligne->produit,
                        magasin: $ligne->magasinDestination,
                        quantite: -(float) $ligne->quantite_pieces,
                        type: MouvementStockType::Annulation,
                        auteur: $auteur,
                        reference: $commandeAchat,
                        motif: $motif,
                    );
                }

                // Reverse la dette exacte posée par crediterDette() à la
                // validation (retrouvée via l'écriture elle-même plutôt que
                // recalculée, pour rester robuste même si le total TTC a
                // bougé depuis — ex. taxe désactivée). Rien à faire si
                // l'achat avait été réglé intégralement à la validation
                // (aucune écriture posée).
                $montantDette = EcritureCompteFournisseur::where('reference_type', $commandeAchat->getMorphClass())
                    ->where('reference_id', $commandeAchat->id)
                    ->where('type', EcritureCompteFournisseurType::AchatCredit)
                    ->value('montant');

                if ($montantDette > 0) {
                    $this->compteFournisseurService->annulerDette($commandeAchat->fournisseur, $montantDette, $commandeAchat, $auteur);
                }
            }
            // Nouveau modèle jamais réceptionné : valider() n'a posé ni
            // mouvement ni dette, rien à réconcilier ici.

            $commandeAchat->motif_annulation = $motif;
            $commandeAchat->annulee_par = $auteur->id;
            $commandeAchat->save();
            $commandeAchat->delete();

            return $commandeAchat;
        });
    }
}
