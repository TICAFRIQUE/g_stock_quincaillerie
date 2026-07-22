<?php

namespace App\Notifications;

use App\Models\Vente;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Alerte in-app envoyée aux gérants du magasin concerné et aux superadmins
 * quand un caissier signale un problème sur une vente (ex. doublon de
 * saisie) — voir VenteController::signaler().
 */
class VenteSignalee extends Notification
{
    use Queueable;

    public function __construct(private readonly Vente $vente, private readonly string $motif) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'vente_id' => $this->vente->id,
            'numero' => $this->vente->numero,
            'magasin' => $this->vente->magasin->nom,
            'caissier' => $this->vente->caissier->name,
            'motif' => $this->motif,
        ];
    }
}
