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
                    quantite: $ligne->quantite,
                    type: MouvementStockType::Reception,
                    auteur: $auteur,
                    reference: $commandeAchat,
                    prixAchat: $ligne->prix_achat,
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
}
