<?php

namespace App\Notifications;

use App\Models\SessionCaisse;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Alerte in-app envoyée aux gérants du magasin concerné et aux superadmins
 * quand une clôture de session révèle un écart de caisse non nul.
 */
class EcartCaisseDetecte extends Notification
{
    use Queueable;

    public function __construct(private readonly SessionCaisse $session) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'session_id' => $this->session->id,
            'caisse' => $this->session->caisse->nom,
            'magasin' => $this->session->caisse->magasin->nom,
            'caissier' => $this->session->caissier->name,
            'ecart' => $this->session->ecart,
            'date_cloture' => $this->session->date_cloture->toIso8601String(),
        ];
    }
}
