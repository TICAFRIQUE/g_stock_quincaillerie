<?php

namespace App\Services;

use App\Enums\MouvementCaisseType;
use App\Exceptions\SoldeCaisseInsuffisantException;
use App\Models\MouvementCaisse;
use App\Models\SessionCaisse;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

/**
 * Point de passage unique pour tout mouvement de caisse manuel (entrée/
 * sortie de tiroir), immuable — même principe que StockService pour le
 * stock. Toujours rattaché à une session ouverte ; une sortie ne peut jamais
 * dépasser le solde théorique du tiroir (voir CLAUDE.md, Mouvements de
 * caisse).
 */
class CaisseMouvementService
{
    public function __construct(private readonly CaisseSessionService $caisseSessionService) {}

    public function enregistrer(
        SessionCaisse $session,
        MouvementCaisseType $type,
        int $montant,
        string $motif,
        User $auteur,
        ?Model $reference = null,
    ): MouvementCaisse {
        if ($montant <= 0) {
            throw new InvalidArgumentException('Le montant du mouvement doit être positif.');
        }

        if ($session->date_cloture !== null || $session->date_fermeture !== null) {
            throw new RuntimeException('Cette session de caisse est fermée : impossible d\'y enregistrer un mouvement.');
        }

        return DB::transaction(function () use ($session, $type, $montant, $motif, $auteur, $reference) {
            // Verrouille la session pour empêcher deux sorties concurrentes
            // de dépasser ensemble le solde théorique du tiroir.
            $session = SessionCaisse::whereKey($session->id)->lockForUpdate()->firstOrFail();

            if ($type === MouvementCaisseType::Sortie) {
                $soldeTheorique = $this->soldeTheorique($session);
                if ($montant > $soldeTheorique) {
                    throw new SoldeCaisseInsuffisantException($session, $montant, $soldeTheorique);
                }
            }

            return MouvementCaisse::create([
                'session_caisse_id' => $session->id,
                'type' => $type,
                'montant' => $montant,
                'motif' => $motif,
                'reference_type' => $reference?->getMorphClass(),
                'reference_id' => $reference?->getKey(),
                'created_by' => $auteur->id,
            ]);
        });
    }

    /**
     * Solde théorique du tiroir en temps réel (session ouverte, pas encore
     * clôturée) : fond de caisse + ventes espèces + règlements clients
     * espèces + entrées − sorties. Réutilise le même calcul que la clôture
     * (voir CaisseSessionService::calculerTheorique()), pour ne jamais avoir
     * deux formules divergentes.
     */
    public function soldeTheorique(SessionCaisse $session): int
    {
        return $this->caisseSessionService->calculerTheorique($session)['theorique'];
    }
}
