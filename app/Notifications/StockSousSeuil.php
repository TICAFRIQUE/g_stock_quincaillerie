<?php

namespace App\Notifications;

use App\Models\Magasin;
use App\Models\Produit;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Alerte in-app envoyée aux gérants du magasin concerné et aux superadmins
 * quand le stock d'un produit franchit son seuil d'alerte à la baisse (ne se
 * redéclenche pas tant que le stock reste sous le seuil — voir StockService).
 */
class StockSousSeuil extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Produit $produit,
        private readonly Magasin $magasin,
        private readonly int|float $quantite,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'produit_id' => $this->produit->id,
            'produit' => $this->produit->libelle_affichage,
            'sku' => $this->produit->sku,
            'magasin_id' => $this->magasin->id,
            'magasin' => $this->magasin->nom,
            'quantite' => $this->quantite,
            'seuil_alerte' => $this->produit->seuil_alerte,
        ];
    }
}
