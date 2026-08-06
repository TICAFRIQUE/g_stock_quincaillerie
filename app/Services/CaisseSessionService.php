<?php

namespace App\Services;

use App\Exceptions\CaisseNonLibreException;
use App\Exceptions\CaissierDejaEnSessionException;
use App\Exceptions\VentesEnAttentePresentesException;
use App\Models\Caisse;
use App\Models\Paiement;
use App\Models\ReglementPaiement;
use App\Models\SessionCaisse;
use App\Models\User;
use App\Models\VenteEnAttente;
use App\Notifications\EcartCaisseDetecte;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use RuntimeException;

/**
 * Cycle de vie d'une session de caisse : ouverture (caisse libre uniquement),
 * clôture (comptage espèces -> écart), fermeture. Ni la clôture ni la
 * fermeture ne sont autorisées tant que des ventes en attente subsistent.
 */
class CaisseSessionService
{
    public function ouvrir(Caisse $caisse, User $caissier, int $fondDeCaisse): SessionCaisse
    {
        return DB::transaction(function () use ($caisse, $caissier, $fondDeCaisse) {
            $caisse = Caisse::whereKey($caisse->id)->lockForUpdate()->firstOrFail();

            $dejaOuverte = SessionCaisse::where('caisse_id', $caisse->id)
                ->whereNull('date_fermeture')
                ->exists();

            if ($dejaOuverte) {
                throw new CaisseNonLibreException($caisse);
            }

            // Un caissier ne peut pas mener deux sessions de front, même sur
            // des caisses différentes — il doit fermer la précédente d'abord.
            $sessionCaissierOuverte = SessionCaisse::where('caissier_id', $caissier->id)
                ->whereNull('date_fermeture')
                ->with('caisse')
                ->first();

            if ($sessionCaissierOuverte) {
                throw new CaissierDejaEnSessionException($sessionCaissierOuverte);
            }

            try {
                return SessionCaisse::create([
                    'caisse_id' => $caisse->id,
                    'caissier_id' => $caissier->id,
                    'fond_de_caisse' => $fondDeCaisse,
                    'date_ouverture' => now(),
                ]);
            } catch (QueryException $e) {
                // Filet de sécurité : la contrainte unique sur est_ouverte a intercepté
                // une course que le verrou applicatif aurait dû empêcher.
                throw new CaisseNonLibreException($caisse);
            }
        });
    }

    public function cloturer(SessionCaisse $session, int $montantCompte, User $auteur): SessionCaisse
    {
        if ($session->date_cloture !== null) {
            throw new RuntimeException('Cette session est déjà clôturée.');
        }

        $this->assertPasDeVenteEnAttente($session);

        $session = DB::transaction(function () use ($session, $montantCompte, $auteur) {
            $totalVentesEspeces = Paiement::query()
                ->whereHas('vente', fn ($q) => $q->where('session_caisse_id', $session->id))
                ->whereHas('moyenPaiement', fn ($q) => $q->where('est_espece', true))
                ->sum('montant');

            // Un règlement client encaissé dans cette session alimente le
            // même tiroir qu'une vente (règle 10 : ventes ET règlements
            // clients confondus).
            $totalReglementsEspeces = ReglementPaiement::query()
                ->whereHas('reglementClient', fn ($q) => $q->where('session_caisse_id', $session->id))
                ->whereHas('moyenPaiement', fn ($q) => $q->where('est_espece', true))
                ->sum('montant');

            $theorique = $session->fond_de_caisse + $totalVentesEspeces + $totalReglementsEspeces;

            $session->update([
                'total_ventes_especes' => $totalVentesEspeces,
                'total_reglements_especes' => $totalReglementsEspeces,
                'montant_compte' => $montantCompte,
                'ecart' => $montantCompte - $theorique,
                'date_cloture' => now(),
                'cloture_by' => $auteur->id,
            ]);

            return $session->refresh();
        });

        if ($session->ecart !== 0) {
            $this->notifierEcart($session);
        }

        return $session;
    }

    /**
     * Alerte les gérants du magasin concerné et les superadmins — envoyée
     * après la transaction pour ne jamais notifier une clôture qui aurait
     * finalement échoué.
     */
    private function notifierEcart(SessionCaisse $session): void
    {
        $session->loadMissing(['caisse.magasin', 'caissier']);

        $destinataires = User::gerantsEtSuperadmins($session->caisse->magasin_id);

        Notification::send($destinataires, new EcartCaisseDetecte($session));
    }

    public function fermer(SessionCaisse $session): SessionCaisse
    {
        if ($session->date_cloture === null) {
            throw new RuntimeException('La session doit être clôturée avant d\'être fermée.');
        }

        $this->assertPasDeVenteEnAttente($session);

        $session->update(['date_fermeture' => now()]);

        return $session->refresh();
    }

    private function assertPasDeVenteEnAttente(SessionCaisse $session): void
    {
        $nombre = VenteEnAttente::where('session_caisse_id', $session->id)->count();

        if ($nombre > 0) {
            throw new VentesEnAttentePresentesException($nombre);
        }
    }
}
