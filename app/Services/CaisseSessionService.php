<?php

namespace App\Services;

use App\Enums\EcritureCompteTresorerieType;
use App\Enums\MouvementCaisseType;
use App\Exceptions\CaisseNonLibreException;
use App\Exceptions\CaissierDejaEnSessionException;
use App\Exceptions\VentesEnAttentePresentesException;
use App\Models\Caisse;
use App\Models\MouvementCaisse;
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
    public function __construct(private readonly CompteTresorerieService $compteTresorerieService) {}

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
            $detail = $this->calculerTheorique($session);

            $session->update([
                'total_ventes_especes' => $detail['ventesEspeces'],
                'total_reglements_especes' => $detail['reglementsEspeces'],
                'total_entrees_especes' => $detail['entrees'],
                'total_sorties_especes' => $detail['sorties'],
                'montant_compte' => $montantCompte,
                'ecart' => $montantCompte - $detail['theorique'],
                'date_cloture' => now(),
                'cloture_by' => $auteur->id,
            ]);

            // Dépôt automatique dans la Caisse Générale : modélise le geste
            // physique de vider le tiroir dans le coffre à la clôture — la
            // Caisse Générale n'a rien à voir avec les caisses des caissiers
            // (voir CLAUDE.md, Trésorerie), c'est le montant réellement
            // compté (pas le théorique) qui y entre.
            if ($montantCompte > 0) {
                $session->loadMissing('caisse');
                $this->compteTresorerieService->crediter(
                    $this->compteTresorerieService->caisseGenerale(),
                    $montantCompte,
                    EcritureCompteTresorerieType::DepotSessionCloturee,
                    $auteur,
                    reference: $session,
                    motif: "Recette {$session->caisse->nom} — session #{$session->id}",
                );
            }

            return $session->refresh();
        });

        if ($session->ecart !== 0) {
            $this->notifierEcart($session);
        }

        return $session;
    }

    /**
     * Détail du solde théorique du tiroir : fond de caisse + ventes espèces
     * + règlements clients espèces (règle 10) + entrées de caisse − sorties
     * de caisse. Point de calcul UNIQUE, réutilisé par cloturer(),
     * l'aperçu avant clôture (SessionCaisseController::cloturerForm()) et le
     * solde théorique temps réel (CaisseMouvementService::soldeTheorique())
     * — pour ne jamais laisser deux formules diverger.
     *
     * @return array{ventesEspeces:int, reglementsEspeces:int, entrees:int, sorties:int, theorique:int}
     */
    public function calculerTheorique(SessionCaisse $session): array
    {
        $ventesEspeces = (int) Paiement::query()
            ->whereHas('vente', fn ($q) => $q->where('session_caisse_id', $session->id))
            ->whereHas('moyenPaiement', fn ($q) => $q->where('est_espece', true))
            ->sum('montant');

        // Un règlement client encaissé dans cette session alimente le même
        // tiroir qu'une vente (règle 10 : ventes ET règlements clients
        // confondus).
        $reglementsEspeces = (int) ReglementPaiement::query()
            ->whereHas('reglementClient', fn ($q) => $q->where('session_caisse_id', $session->id))
            ->whereHas('moyenPaiement', fn ($q) => $q->where('est_espece', true))
            ->sum('montant');

        $entrees = (int) MouvementCaisse::where('session_caisse_id', $session->id)
            ->where('type', MouvementCaisseType::Entree)
            ->sum('montant');

        $sorties = (int) MouvementCaisse::where('session_caisse_id', $session->id)
            ->where('type', MouvementCaisseType::Sortie)
            ->sum('montant');

        $theorique = $session->fond_de_caisse + $ventesEspeces + $reglementsEspeces + $entrees - $sorties;

        return compact('ventesEspeces', 'reglementsEspeces', 'entrees', 'sorties', 'theorique');
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
