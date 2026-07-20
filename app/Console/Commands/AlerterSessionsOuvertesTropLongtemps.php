<?php

namespace App\Console\Commands;

use App\Models\SessionCaisse;
use App\Models\User;
use App\Notifications\SessionOuverteTropLongtemps;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

/**
 * Signale aux gérants/superadmins une session ni clôturée ni fermée depuis
 * plus de SEUIL_HEURES — typiquement un caissier qui a oublié de clôturer
 * avant de se déconnecter. Une seule alerte par session (alerte_ouverture_
 * envoyee_at), pour ne pas spammer à chaque exécution planifiée.
 */
#[Signature('app:alerter-sessions-ouvertes-trop-longtemps')]
#[Description('Alerte les gérants/superadmins des sessions de caisse restées ouvertes trop longtemps')]
class AlerterSessionsOuvertesTropLongtemps extends Command
{
    private const SEUIL_HEURES = 12;

    public function handle(): void
    {
        $sessions = SessionCaisse::query()
            ->with(['caisse.magasin', 'caissier'])
            ->whereNull('date_cloture')
            ->whereNull('alerte_ouverture_envoyee_at')
            ->where('date_ouverture', '<=', now()->subHours(self::SEUIL_HEURES))
            ->get();

        foreach ($sessions as $session) {
            $destinataires = User::gerantsEtSuperadmins($session->caisse->magasin_id);

            Notification::send($destinataires, new SessionOuverteTropLongtemps($session));

            $session->update(['alerte_ouverture_envoyee_at' => now()]);
        }

        $this->info("{$sessions->count()} session(s) signalée(s).");
    }
}
