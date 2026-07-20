<?php

namespace App\Notifications;

use App\Models\SessionCaisse;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Alerte in-app envoyée aux gérants du magasin concerné et aux superadmins
 * quand une session reste ouverte (ni clôturée ni fermée) au-delà du seuil
 * défini dans AlerterSessionsOuvertesTropLongtemps — signe probable d'un
 * caissier qui a oublié de clôturer avant de se déconnecter.
 */
class SessionOuverteTropLongtemps extends Notification
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
            'date_ouverture' => $this->session->date_ouverture->toIso8601String(),
            'heures_ecoulees' => (int) $this->session->date_ouverture->diffInHours(now()),
        ];
    }
}
