<?php

namespace App\Services;

use App\Exceptions\SessionNonOuverteException;
use App\Models\SessionCaisse;
use App\Models\User;
use App\Models\Vente;
use App\Models\VenteEnAttente;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Panier mis de côté le temps de servir un autre client : aucun mouvement de
 * stock, aucun encaissement. La reprise applique le prix courant des produits
 * (pas de figeage à la mise en attente) — l'autorisation (qui peut voir/
 * annuler quoi) relève de la couche policy/contrôleur, pas de ce service.
 */
class VenteEnAttenteService
{
    public function __construct(private readonly VenteService $venteService) {}

    /**
     * @param  array<int, array{produit_id:int, unite_vente_id?:?int, quantite:int}>  $lignes
     */
    public function mettreEnAttente(
        SessionCaisse $session,
        User $caissier,
        array $lignes,
        ?string $libelle = null,
    ): VenteEnAttente {
        if (empty($lignes)) {
            throw new InvalidArgumentException('Une vente en attente doit comporter au moins une ligne.');
        }

        $session = $session->fresh();
        if ($session->date_cloture !== null || $session->date_fermeture !== null) {
            throw new SessionNonOuverteException();
        }

        return DB::transaction(function () use ($session, $caissier, $lignes, $libelle) {
            $venteEnAttente = VenteEnAttente::create([
                'magasin_id' => $session->caisse->magasin_id,
                'caisse_id' => $session->caisse_id,
                'session_caisse_id' => $session->id,
                'caissier_id' => $caissier->id,
                'libelle' => $libelle,
            ]);

            foreach ($lignes as $ligne) {
                $venteEnAttente->lignes()->create([
                    'produit_id' => $ligne['produit_id'],
                    'unite_vente_id' => $ligne['unite_vente_id'] ?? null,
                    'quantite' => $ligne['quantite'],
                ]);
            }

            return $venteEnAttente;
        });
    }

    /**
     * @param  array<int, array{moyen_paiement_id:int, montant:int}>  $paiements
     */
    public function reprendre(
        VenteEnAttente $venteEnAttente,
        array $paiements,
        ?string $remiseTotaleType = null,
        ?int $remiseTotaleValeur = null,
    ): Vente {
        $venteEnAttente->loadMissing(['lignes', 'sessionCaisse', 'caissier']);

        $lignes = $venteEnAttente->lignes->map(fn ($l) => [
            'produit_id' => $l->produit_id,
            'unite_vente_id' => $l->unite_vente_id,
            'quantite' => $l->quantite,
        ])->all();

        return DB::transaction(function () use ($venteEnAttente, $lignes, $paiements, $remiseTotaleType, $remiseTotaleValeur) {
            $vente = $this->venteService->vendre(
                session: $venteEnAttente->sessionCaisse,
                caissier: $venteEnAttente->caissier,
                lignes: $lignes,
                paiements: $paiements,
                remiseTotaleType: $remiseTotaleType,
                remiseTotaleValeur: $remiseTotaleValeur,
            );

            $venteEnAttente->delete();

            return $vente;
        });
    }

    public function annuler(VenteEnAttente $venteEnAttente): void
    {
        $venteEnAttente->delete();
    }
}
