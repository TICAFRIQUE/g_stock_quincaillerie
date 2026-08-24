<?php

namespace App\Services;

use App\Enums\EcritureCompteTresorerieType;
use App\Models\CommandeAchat;
use App\Models\Fournisseur;
use App\Models\MoyenPaiement;
use App\Models\ReglementFournisseur;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Paiement d'une dette fournisseur, immuable comme ReglementClientService.
 * Indépendant de toute session de caisse (règle 17) : la part payée en
 * espèces sort directement de la Caisse Générale (voir CLAUDE.md,
 * Trésorerie), jamais du tiroir d'un caissier — cet argent n'a rien à voir
 * avec les caisses des caissiers.
 */
class ReglementFournisseurService
{
    public function __construct(
        private readonly CompteFournisseurService $compteFournisseurService,
        private readonly CompteTresorerieService $compteTresorerieService,
    ) {}

    /**
     * @param  array<int, array{moyen_paiement_id:int, montant:int}>  $paiements
     */
    public function encaisser(
        Fournisseur $fournisseur,
        User $auteur,
        array $paiements,
        ?CommandeAchat $commandeAchat = null,
    ): ReglementFournisseur {
        if (empty($paiements)) {
            throw new InvalidArgumentException('Un règlement doit comporter au moins un paiement.');
        }

        $montant = array_sum(array_column($paiements, 'montant'));
        if ($montant <= 0) {
            throw new InvalidArgumentException('Le montant du règlement doit être positif.');
        }

        $moyensEspeces = MoyenPaiement::where('est_espece', true)->pluck('id')->all();
        $montantEspeces = array_sum(array_map(
            fn (array $p) => in_array($p['moyen_paiement_id'], $moyensEspeces) ? $p['montant'] : 0,
            $paiements
        ));

        return DB::transaction(function () use ($fournisseur, $auteur, $paiements, $montant, $commandeAchat, $montantEspeces) {
            $reglement = ReglementFournisseur::create([
                'fournisseur_id' => $fournisseur->id,
                'commande_achat_id' => $commandeAchat?->id,
                'created_by' => $auteur->id,
                'montant' => $montant,
            ]);

            foreach ($paiements as $p) {
                $reglement->paiements()->create([
                    'moyen_paiement_id' => $p['moyen_paiement_id'],
                    'montant' => $p['montant'],
                ]);
            }

            // Lève SoldeFournisseurInsuffisantException si le règlement
            // dépasse la dette actuelle, ce qui annule toute la transaction.
            $this->compteFournisseurService->enregistrerReglement($fournisseur, $montant, $reglement, $auteur);

            if ($montantEspeces > 0) {
                // Peut lever SoldeTresorerieInsuffisantException si la
                // Caisse Générale n'a pas assez d'espèces — annule tout.
                $this->compteTresorerieService->debiter(
                    $this->compteTresorerieService->caisseGenerale(),
                    $montantEspeces,
                    EcritureCompteTresorerieType::ReglementFournisseur,
                    $auteur,
                    reference: $reglement,
                    motif: "Règlement fournisseur {$fournisseur->nom}",
                );
            }

            return $reglement->refresh();
        });
    }

    /**
     * Règlement global : solde du compte réglé en une fois, réparti
     * automatiquement sur chaque commande encore due (la plus ancienne
     * d'abord) — une ReglementFournisseur PAR commande, jamais un règlement
     * "en l'air" non imputé. Contrairement à encaisser() ciblant une
     * commande précise, aucun paiement partiel n'est permis ici : le montant
     * doit couvrir EXACTEMENT le solde actuel ; un paiement partiel doit
     * obligatoirement cibler une commande précise (encaisser()) — sans ça,
     * CommandeAchat::resteDu() de chaque commande ne bougeait jamais après
     * un règlement global, alors que le solde du compte, lui, diminuait bien.
     *
     * @param  array<int, array{moyen_paiement_id:int, montant:int}>  $paiements
     * @return Collection<int, ReglementFournisseur>
     */
    public function reglerIntegralite(Fournisseur $fournisseur, User $auteur, array $paiements): Collection
    {
        if (empty($paiements)) {
            throw new InvalidArgumentException('Un règlement doit comporter au moins un paiement.');
        }

        $montantTotal = array_sum(array_column($paiements, 'montant'));
        if ($montantTotal <= 0) {
            throw new InvalidArgumentException('Le montant du règlement doit être positif.');
        }

        $solde = $this->compteFournisseurService->solde($fournisseur);
        if ($montantTotal !== $solde) {
            throw new InvalidArgumentException(
                "Un règlement global doit couvrir l'intégralité du solde ({$solde} F) — pour un paiement partiel, réglez une commande précise depuis la liste des bons d'achat."
            );
        }

        $commandesDues = $fournisseur->commandeAchats()
            ->where('statut', 'validee')
            ->with('lignes', 'paiements', 'reglementsFournisseur')
            ->orderBy('date_commande')
            ->orderBy('id')
            ->get()
            ->filter(fn (CommandeAchat $c) => $c->resteDu() > 0)
            ->values();

        $moyensEspeces = MoyenPaiement::where('est_espece', true)->pluck('id')->all();

        return DB::transaction(function () use ($fournisseur, $auteur, $paiements, $commandesDues, $moyensEspeces) {
            // Répartition "en cascade" (comme faire l'appoint) : chaque
            // commande, de la plus ancienne à la plus récente, puise dans les
            // paiements restants jusqu'à couvrir son propre reste dû — une
            // opération purement entière, sans division ni pourcentage, donc
            // jamais d'arrondi à gérer.
            $paiementsRestants = array_map(fn (array $p) => ['moyen_paiement_id' => $p['moyen_paiement_id'], 'montant' => $p['montant']], $paiements);
            $reglements = collect();

            foreach ($commandesDues as $commande) {
                $aAllouer = $commande->resteDu();
                $paiementsCommande = [];

                while ($aAllouer > 0 && ! empty($paiementsRestants)) {
                    $pris = min($paiementsRestants[0]['montant'], $aAllouer);
                    $paiementsCommande[] = ['moyen_paiement_id' => $paiementsRestants[0]['moyen_paiement_id'], 'montant' => $pris];
                    $paiementsRestants[0]['montant'] -= $pris;
                    $aAllouer -= $pris;
                    if ($paiementsRestants[0]['montant'] <= 0) {
                        array_shift($paiementsRestants);
                    }
                }

                if (empty($paiementsCommande)) {
                    break;
                }

                $montantCommande = array_sum(array_column($paiementsCommande, 'montant'));

                $reglement = ReglementFournisseur::create([
                    'fournisseur_id' => $fournisseur->id,
                    'commande_achat_id' => $commande->id,
                    'created_by' => $auteur->id,
                    'montant' => $montantCommande,
                ]);

                foreach ($paiementsCommande as $p) {
                    $reglement->paiements()->create($p);
                }

                $this->compteFournisseurService->enregistrerReglement($fournisseur, $montantCommande, $reglement, $auteur);

                $montantEspeces = array_sum(array_map(
                    fn (array $p) => in_array($p['moyen_paiement_id'], $moyensEspeces) ? $p['montant'] : 0,
                    $paiementsCommande
                ));
                if ($montantEspeces > 0) {
                    $this->compteTresorerieService->debiter(
                        $this->compteTresorerieService->caisseGenerale(),
                        $montantEspeces,
                        EcritureCompteTresorerieType::ReglementFournisseur,
                        $auteur,
                        reference: $reglement,
                        motif: "Règlement fournisseur {$fournisseur->nom} — {$commande->numero}",
                    );
                }

                $reglements->push($reglement->refresh());
            }

            // Ne devrait jamais arriver si le montant == solde et que tous
            // les règlements précédents ont bien été imputés à une commande —
            // filet de sécurité plutôt qu'une répartition silencieusement
            // incomplète (voir commentaire de méthode).
            if (array_sum(array_column($paiementsRestants, 'montant')) > 0) {
                throw new InvalidArgumentException(
                    'Le montant dépasse la somme des restes dus des commandes ouvertes de ce fournisseur.'
                );
            }

            return $reglements;
        });
    }
}
