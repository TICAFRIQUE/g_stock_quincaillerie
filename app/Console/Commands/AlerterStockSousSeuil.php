<?php

namespace App\Console\Commands;

use App\Models\Stock;
use App\Models\User;
use App\Notifications\StockSousSeuil;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

/**
 * Filet de sécurité complémentaire à l'alerte déclenchée en temps réel dans
 * StockService : repère les stocks sous seuil dont l'épisode d'alerte n'a
 * jamais été notifié — typiquement un stock déjà bas avant l'activation de
 * cette fonctionnalité, ou un produit qui ne reçoit plus aucun mouvement.
 */
#[Signature('app:alerter-stock-sous-seuil')]
#[Description('Alerte les gérants/superadmins des stocks sous seuil jamais notifiés')]
class AlerterStockSousSeuil extends Command
{
    public function handle(): void
    {
        $stocks = Stock::query()
            ->join('produits', 'produits.id', '=', 'stocks.produit_id')
            ->whereColumn('stocks.quantite', '<=', 'produits.seuil_alerte')
            ->whereNull('stocks.alerte_seuil_envoyee_at')
            ->select('stocks.*')
            ->with(['produit', 'magasin'])
            ->get();

        foreach ($stocks as $stock) {
            $destinataires = User::gerantsEtSuperadmins($stock->magasin_id);

            Notification::send($destinataires, new StockSousSeuil($stock->produit, $stock->magasin, $stock->quantite));

            $stock->update(['alerte_seuil_envoyee_at' => now()]);
        }

        $this->info("{$stocks->count()} produit(s) sous seuil signalé(s).");
    }
}
