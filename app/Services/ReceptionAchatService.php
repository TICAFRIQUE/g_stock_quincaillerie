<?php

namespace App\Services;

use App\Enums\MouvementStockType;
use App\Exceptions\QuantiteReceptionInvalideException;
use App\Models\CommandeAchat;
use App\Models\LigneReceptionAchat;
use App\Models\Magasin;
use App\Models\ReceptionAchat;
use App\Models\User;
use App\Support\NumeroDocument;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Réception fournisseur : toujours liée à une commande d'achat confirmée
 * (validée), ligne par ligne, plafonnée à la quantité commandée restant à
 * recevoir sur chaque ligne — même principe de cumul que
 * BonLivraisonService/RetourAchatService. Contrairement au bon de livraison,
 * mouvemente réellement le stock (CMP recalculé au prix réel facturé, qui
 * peut différer de l'indicatif de la commande) et pose la dette fournisseur
 * pour le reste dû de CETTE réception — voir CLAUDE.md.
 */
class ReceptionAchatService
{
    public function __construct(
        private readonly StockService $stockService,
        private readonly CompteFournisseurService $compteFournisseurService,
    ) {}

    /**
     * @param  array<int, array{ligne_commande_achat_id:int, quantite_pieces:int|float, prix_achat_reel:int, magasin_id:int}>  $lignes
     * @param  array<int, array{moyen_paiement_id:int, montant:int}>  $paiements
     */
    public function receptionner(CommandeAchat $commandeAchat, array $lignes, User $auteur, array $paiements = [], ?string $motif = null, ?string $numeroFactureFournisseur = null, ?string $numeroBonLivraisonFournisseur = null): ReceptionAchat
    {
        if ($commandeAchat->trashed()) {
            throw new InvalidArgumentException("Impossible de réceptionner une commande annulée.");
        }

        if ($commandeAchat->statut !== 'validee') {
            throw new InvalidArgumentException("Seule une commande confirmée (validée) peut être réceptionnée.");
        }

        $lignes = array_values(array_filter($lignes, fn (array $l) => ($l['quantite_pieces'] ?? 0) > 0));

        if (empty($lignes)) {
            throw new InvalidArgumentException("Une réception doit comporter au moins une ligne avec une quantité positive.");
        }

        $commandeAchat->loadMissing('lignes.produit', 'fournisseur');

        $magasinIds = collect($lignes)->pluck('magasin_id')->filter()->unique();
        $magasinsParId = $magasinIds->isNotEmpty()
            ? Magasin::whereIn('id', $magasinIds)->get()->keyBy('id')
            : collect();

        // Une seule requête groupée pour les quantités déjà reçues de toutes
        // les lignes de la commande (zéro N+1 — règle Performance).
        $dejaRecu = LigneReceptionAchat::whereIn('ligne_commande_achat_id', $commandeAchat->lignes->pluck('id'))
            ->selectRaw('ligne_commande_achat_id, SUM(quantite_pieces) as total')
            ->groupBy('ligne_commande_achat_id')
            ->pluck('total', 'ligne_commande_achat_id');

        return DB::transaction(function () use ($commandeAchat, $lignes, $auteur, $paiements, $motif, $numeroFactureFournisseur, $numeroBonLivraisonFournisseur, $dejaRecu, $magasinsParId) {
            $reception = ReceptionAchat::create([
                'numero' => $this->genererNumero(),
                'commande_achat_id' => $commandeAchat->id,
                'motif' => $motif,
                'numero_facture_fournisseur' => $numeroFactureFournisseur,
                'numero_bon_livraison_fournisseur' => $numeroBonLivraisonFournisseur,
                'created_by' => $auteur->id,
            ]);

            foreach ($lignes as $demande) {
                $ligne = $commandeAchat->lignes->firstWhere('id', $demande['ligne_commande_achat_id']);

                if (! $ligne) {
                    throw new InvalidArgumentException("Ligne de commande introuvable sur ce bon d'achat.");
                }

                $magasin = $magasinsParId->get($demande['magasin_id'] ?? null);
                if ($magasin === null) {
                    throw new InvalidArgumentException('Destination introuvable pour une ligne de réception.');
                }

                $quantiteAvant = (float) ($dejaRecu[$ligne->id] ?? 0);
                $quantiteRestante = (float) $ligne->quantite_pieces - $quantiteAvant;
                $quantiteRecue = (float) $demande['quantite_pieces'];

                if ($quantiteRecue > $quantiteRestante) {
                    throw new QuantiteReceptionInvalideException($ligne, $quantiteRecue, $quantiteRestante);
                }

                $prixReel = (int) $demande['prix_achat_reel'];

                $reception->lignes()->create([
                    'ligne_commande_achat_id' => $ligne->id,
                    'produit_id' => $ligne->produit_id,
                    'magasin_id' => $magasin->id,
                    'quantite_pieces' => $quantiteRecue,
                    'prix_achat_reel' => $prixReel,
                    'taxe_id' => $ligne->taxe_id,
                ]);

                $this->stockService->enregistrerMouvement(
                    produit: $ligne->produit,
                    magasin: $magasin,
                    quantite: $quantiteRecue,
                    type: MouvementStockType::Reception,
                    auteur: $auteur,
                    reference: $reception,
                    prixAchat: $prixReel,
                );

                $dejaRecu[$ligne->id] = $quantiteAvant + $quantiteRecue;
            }

            $reception = $reception->fresh('lignes.taxe');
            $totalTtc = $reception->totalTtc();
            $totalPaiements = array_sum(array_column($paiements, 'montant'));

            if ($totalPaiements > $totalTtc) {
                throw new InvalidArgumentException(
                    "Le total des paiements ({$totalPaiements}) ne peut pas dépasser le total TTC de la réception ({$totalTtc})."
                );
            }

            foreach ($paiements as $p) {
                $reception->paiements()->create([
                    'commande_achat_id' => $commandeAchat->id,
                    'moyen_paiement_id' => $p['moyen_paiement_id'],
                    'montant' => $p['montant'],
                ]);
            }

            $resteDu = $totalTtc - $totalPaiements;
            if ($resteDu > 0) {
                $this->compteFournisseurService->crediterDette($commandeAchat->fournisseur, $resteDu, $reception, $auteur);
            }

            return $reception->fresh(['lignes.taxe', 'paiements']);
        });
    }

    private function genererNumero(): string
    {
        return NumeroDocument::genererUnique('BA', ReceptionAchat::class);
    }
}
