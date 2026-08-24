@extends('layouts.app')

@section('title', $fournisseur->nom)

@section('content')
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h2 class="h4 mb-1">{{ $fournisseur->nom }} <code class="fs-6 text-secondary">{{ $fournisseur->code }}</code></h2>
            <p class="text-secondary small mb-0">
                {{ $fournisseur->telephone ?? 'Aucun téléphone' }}
                @if ($fournisseur->email) — {{ $fournisseur->email }} @endif
                @if ($fournisseur->adresse) — {{ $fournisseur->adresse }} @endif
            </p>
        </div>
        <div class="d-flex gap-2">
            @can('fournisseur.reglement')
                @if ($solde > 0)
                    <button type="button" class="btn btn-reglement" data-bs-toggle="modal" data-bs-target="#reglerFournisseurModal">
                        <i class="bi bi-cash-coin me-1"></i>Régler
                    </button>
                @endif
                @if ($solde < 0)
                    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#rembourserAvoirFournisseurModal">
                        <i class="bi bi-arrow-return-left me-1"></i>Encaisser l'avoir
                    </button>
                @endif
            @endcan
            @can('fournisseur.gerer')
                <a href="{{ route('fournisseurs.edit', $fournisseur) }}" class="btn btn-outline-secondary">
                    <i class="bi bi-pencil me-1"></i>Modifier
                </a>
            @endcan
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-md-4 col-lg-2">
            <x-kpi-card label="Solde du compte" icon="bi-cash-stack" :color="$solde > 0 ? 'danger' : 'success'"
                :value="number_format($solde, 0, ',', ' ') . ' F' . ($solde < 0 ? ' (avoir)' : '')" />
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <x-kpi-card label="Total achats" icon="bi-graph-up-arrow" color="info"
                :value="number_format($totalAchats, 0, ',', ' ') . ' F'" />
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <x-kpi-card label="Bons d'achat" icon="bi-truck" color="primary"
                :value="$fournisseur->commande_achats_count" />
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <x-kpi-card label="Total réglé" icon="bi-check2-circle" color="success"
                :value="number_format($totalRegle, 0, ',', ' ') . ' F'" />
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <x-kpi-card label="Panier moyen" icon="bi-basket" color="warning"
                :value="number_format($nombreAchats > 0 ? intdiv($totalAchats, $nombreAchats) : 0, 0, ',', ' ') . ' F'" />
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <h3 class="h6 mb-0">Bons d'achat</h3>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Numéro</th>
                        <th>Date</th>
                        <th>Destination(s)</th>
                        <th>Statut</th>
                        <th class="text-end">Total TTC</th>
                        <th class="text-end">Réglé</th>
                        <th class="text-end">Reste dû</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($commandes as $commande)
                        <tr>
                            <td><code>{{ $commande->numero }}</code></td>
                            <td>{{ $commande->created_at->format('d/m/Y H:i') }}</td>
                            <td>{{ $commande->lignes->pluck('magasinDestination.nom')->unique()->implode(', ') }}</td>
                            <td>
                                @if ($commande->trashed())
                                    <span class="badge text-bg-danger">Annulée</span>
                                @elseif ($commande->statut === 'validee')
                                    <span class="badge text-bg-success">Validée</span>
                                @else
                                    <span class="badge text-bg-secondary">Brouillon</span>
                                @endif
                            </td>
                            <td class="text-end">{{ number_format($commande->totalTtc(), 0, ',', ' ') }} F</td>
                            @if ($commande->statut === 'validee')
                                <td class="text-end text-success">{{ number_format($commande->montantRegle(), 0, ',', ' ') }} F</td>
                                <td class="text-end {{ $commande->resteDu() > 0 ? 'text-danger fw-medium' : 'text-secondary' }}">{{ number_format($commande->resteDu(), 0, ',', ' ') }} F</td>
                            @else
                                <td class="text-end text-secondary">—</td>
                                <td class="text-end text-secondary">—</td>
                            @endif
                            <td class="text-end">
                                <a href="{{ route('commande-achats.show', $commande) }}" class="btn btn-sm btn-icon btn-outline-secondary" title="Détail du bon d'achat">
                                    <i class="bi bi-eye"></i>
                                    <span class="visually-hidden">Détail</span>
                                </a>
                                @can('fournisseur.reglement')
                                    @if ($commande->statut === 'validee' && ! $commande->trashed() && $commande->resteDu() > 0)
                                        <button type="button" class="btn btn-sm btn-icon btn-outline-reglement" title="Régler cette dette"
                                                data-bs-toggle="modal" data-bs-target="#reglerFournisseurModal"
                                                data-montant="{{ min($commande->resteDu(), $solde) }}"
                                                data-commande="{{ $commande->id }}"
                                                data-numero="{{ $commande->numero }}">
                                            <i class="bi bi-cash-coin"></i>
                                            <span class="visually-hidden">Régler</span>
                                        </button>
                                    @endif
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-secondary py-4">Aucune commande pour ce fournisseur.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($commandes->hasPages())
            <div class="card-body">
                {{ $commandes->onEachSide(1)->links() }}
            </div>
        @endif
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <h3 class="h6">Historique du compte</h3>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Référence</th>
                        <th>Auteur</th>
                        <th class="text-end">Montant</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($ecritures as $ecriture)
                        <tr class="{{ $ecriture->type->classeLigne() }}">
                            <td>{{ $ecriture->created_at->format('d/m/Y H:i') }}</td>
                            <td><span class="badge {{ $ecriture->type->classeBadge() }}">{{ $ecriture->type->libelle() }}</span></td>
                            @php
                                $commandeLiee = match (true) {
                                    $ecriture->reference instanceof \App\Models\CommandeAchat => $ecriture->reference,
                                    $ecriture->reference instanceof \App\Models\ReglementFournisseur => $ecriture->reference->commandeAchat,
                                    $ecriture->reference instanceof \App\Models\RetourAchat => $ecriture->reference->commandeAchat,
                                    default => null,
                                };
                            @endphp
                            <td>
                                @if ($commandeLiee)
                                    <a href="{{ route('commande-achats.show', $commandeLiee) }}">{{ $commandeLiee->numero }}</a>
                                @elseif ($ecriture->reference instanceof \App\Models\ReglementFournisseur)
                                    <span class="text-secondary fst-italic">Règlement général</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $ecriture->auteur?->name ?? 'Utilisateur supprimé' }}</td>
                            <td class="text-end fw-medium {{ $ecriture->montant > 0 ? 'text-danger' : 'text-success' }}">
                                {{ $ecriture->montant > 0 ? '+' : '' }}{{ number_format($ecriture->montant, 0, ',', ' ') }} F
                            </td>
                            <td class="text-end">
                                @if ($commandeLiee)
                                    <a href="{{ route('commande-achats.show', $commandeLiee) }}" class="btn btn-sm btn-icon btn-outline-secondary" title="Voir le détail du bon d'achat">
                                        <i class="bi bi-eye"></i>
                                        <span class="visually-hidden">Détail</span>
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-secondary py-4">Aucun mouvement sur ce compte.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($ecritures->hasPages())
            <div class="card-body">
                {{ $ecritures->onEachSide(1)->links() }}
            </div>
        @endif
    </div>

    <div class="mt-3">
        <a href="{{ route('fournisseurs.index') }}" class="btn btn-link ps-0">Retour à la liste</a>
    </div>

    @can('fournisseur.reglement')
        @if ($solde > 0)
            <div class="modal fade" id="reglerFournisseurModal" tabindex="-1" aria-hidden="true"
                 x-data="{
                     paiements: [{ moyen_paiement_id: '', montant: null }],
                     commandeAchatId: null,
                     commandeNumero: null,
                     detteAffichee: {{ $solde }},
                     especeIds: @json($moyensPaiement->where('est_espece', true)->pluck('id')->values()),
                     get totalPaiements() { return this.paiements.reduce((total, p) => total + (Number(p.montant) || 0), 0); },
                     get contientEspeces() { return this.paiements.some(p => this.especeIds.includes(Number(p.moyen_paiement_id))); },
                     // Un règlement ciblant une commande précise reste partiel
                     // (totalPaiements <= detteAffichee suffit) ; un règlement
                     // global (aucune commande ciblée) doit couvrir le solde à
                     // l'exact franc près — voir ReglementFournisseurService::
                     // reglerIntegralite(), qui répartit automatiquement sur
                     // chaque commande due et refuse tout écart.
                     get montantValide() { return this.commandeAchatId ? this.totalPaiements <= this.detteAffichee : this.totalPaiements === this.detteAffichee; },
                     ajouterPaiement() { this.paiements.push({ moyen_paiement_id: '', montant: null }); },
                     retirerPaiement(index) { if (this.paiements.length > 1) this.paiements.splice(index, 1); },
                     onModalShow(event) {
                         this.commandeAchatId = event.relatedTarget?.dataset.commande || null;
                         this.commandeNumero = event.relatedTarget?.dataset.numero || null;
                         const montant = event.relatedTarget?.dataset.montant ? Number(event.relatedTarget.dataset.montant) : null;
                         // Réglement global : un seul paiement, pré-rempli au
                         // solde entier — pour un paiement mixte (plusieurs
                         // moyens), le bouton d'ajout de paiement reste
                         // possible, mais la somme doit toujours retomber
                         // exactement sur le solde (montantValide ci-dessus).
                         this.paiements = [{ moyen_paiement_id: '', montant: this.commandeAchatId ? montant : {{ $solde }} }];
                         this.detteAffichee = this.commandeAchatId ? montant : {{ $solde }};
                     },
                 }"
                 x-init="$el.addEventListener('show.bs.modal', (event) => onModalShow(event))">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('reglements-fournisseur.store', $fournisseur) }}">
                            @csrf
                            <input type="hidden" name="commande_achat_id" :value="commandeAchatId">
                            <div class="modal-header">
                                <h5 class="modal-title">
                                    <i class="bi bi-cash-coin text-reglement me-2"></i>Régler {{ $fournisseur->nom }}
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                            </div>
                            <div class="modal-body">
                                <p class="small text-secondary">
                                    <template x-if="commandeNumero">
                                        <span>Reste dû sur le bon d'achat <strong x-text="commandeNumero"></strong> : <strong x-text="detteAffichee.toLocaleString('fr-FR')"></strong> F</span>
                                    </template>
                                    <template x-if="!commandeNumero">
                                        <span>
                                            Dette totale du fournisseur : <strong>{{ number_format($solde, 0, ',', ' ') }} F</strong> — ce règlement
                                            soldera l'intégralité de la dette, répartie automatiquement sur chaque bon d'achat encore dû
                                            (le plus ancien d'abord). Pour un paiement partiel, réglez un bon d'achat précis depuis la liste ci-dessous.
                                        </span>
                                    </template>
                                </p>

                                <label class="form-label">Paiement</label>
                                <template x-for="(paiement, index) in paiements" :key="index">
                                    <div class="row g-1 align-items-center mb-2">
                                        <div class="col-6">
                                            <select :name="'paiements['+index+'][moyen_paiement_id]'" x-model="paiement.moyen_paiement_id" class="form-select form-select-sm" required>
                                                <option value="">Moyen…</option>
                                                @foreach ($moyensPaiement as $moyen)
                                                    <option value="{{ $moyen->id }}">{{ $moyen->nom }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-4">
                                            <input type="number" :name="'paiements['+index+'][montant]'" x-model.number="paiement.montant" min="1" class="form-control form-control-sm" placeholder="Montant" required>
                                        </div>
                                        <div class="col-2">
                                            <button type="button" class="btn btn-sm btn-icon btn-outline-danger" @click="retirerPaiement(index)" x-show="paiements.length > 1">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        </div>
                                    </div>
                                </template>
                                <button type="button" class="btn btn-sm btn-outline-reglement" @click="ajouterPaiement()">
                                    <i class="bi bi-plus-lg"></i> Ajouter un moyen de paiement
                                </button>

                                <div class="mt-3 small text-secondary" x-show="contientEspeces" x-cloak>
                                    <i class="bi bi-safe me-1"></i>Paiement en espèces : sort de la Caisse Générale.
                                </div>

                                <div class="mt-3 small" :class="!montantValide ? 'text-danger' : 'text-secondary'">
                                    Total réglé : <span x-text="totalPaiements"></span> F
                                    <template x-if="commandeAchatId">
                                        <span x-show="totalPaiements > detteAffichee">— dépasse le montant dû.</span>
                                    </template>
                                    <template x-if="!commandeAchatId">
                                        <span x-show="!montantValide">— un règlement global doit couvrir exactement la dette totale ({{ number_format($solde, 0, ',', ' ') }} F).</span>
                                    </template>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-link" data-bs-dismiss="modal">Annuler</button>
                                <button type="submit" class="btn btn-reglement" :disabled="totalPaiements <= 0 || !montantValide">
                                    <i class="bi bi-check-circle me-1"></i>Enregistrer le règlement
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    @endcan

    @can('fournisseur.reglement')
        @if ($solde < 0)
            <div class="modal fade" id="rembourserAvoirFournisseurModal" tabindex="-1" aria-hidden="true"
                 x-data="{
                     paiements: [{ moyen_paiement_id: '', montant: {{ -$solde }} }],
                     avoirDisponible: {{ -$solde }},
                     especeIds: @json($moyensPaiement->where('est_espece', true)->pluck('id')->values()),
                     get totalPaiements() { return this.paiements.reduce((total, p) => total + (Number(p.montant) || 0), 0); },
                     get contientEspeces() { return this.paiements.some(p => this.especeIds.includes(Number(p.moyen_paiement_id))); },
                     ajouterPaiement() { this.paiements.push({ moyen_paiement_id: '', montant: null }); },
                     retirerPaiement(index) { if (this.paiements.length > 1) this.paiements.splice(index, 1); },
                 }">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('remboursements-avoir-fournisseur.store', $fournisseur) }}">
                            @csrf
                            <div class="modal-header">
                                <h5 class="modal-title">
                                    <i class="bi bi-arrow-return-left text-primary me-2"></i>Encaisser l'avoir de {{ $fournisseur->nom }}
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                            </div>
                            <div class="modal-body">
                                <p class="small text-secondary">
                                    Avoir disponible : <strong>{{ number_format(-$solde, 0, ',', ' ') }} F</strong>
                                    — l'argent que le fournisseur nous reverse, jamais plus que cet avoir.
                                </p>

                                <label class="form-label">Encaissement</label>
                                <template x-for="(paiement, index) in paiements" :key="index">
                                    <div class="row g-1 align-items-center mb-2">
                                        <div class="col-6">
                                            <select :name="'paiements['+index+'][moyen_paiement_id]'" x-model="paiement.moyen_paiement_id" class="form-select form-select-sm" required>
                                                <option value="">Moyen…</option>
                                                @foreach ($moyensPaiement as $moyen)
                                                    <option value="{{ $moyen->id }}">{{ $moyen->nom }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-4">
                                            <input type="number" :name="'paiements['+index+'][montant]'" x-model.number="paiement.montant" min="1" class="form-control form-control-sm" placeholder="Montant" required>
                                        </div>
                                        <div class="col-2">
                                            <button type="button" class="btn btn-sm btn-icon btn-outline-danger" @click="retirerPaiement(index)" x-show="paiements.length > 1">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        </div>
                                    </div>
                                </template>
                                <button type="button" class="btn btn-sm btn-outline-primary" @click="ajouterPaiement()">
                                    <i class="bi bi-plus-lg"></i> Ajouter un moyen de paiement
                                </button>

                                <div class="mt-3 small text-secondary" x-show="contientEspeces" x-cloak>
                                    <i class="bi bi-safe me-1"></i>Encaissement en espèces : entre dans la Caisse Générale.
                                </div>

                                <div class="mt-3 small" :class="totalPaiements > avoirDisponible ? 'text-danger' : 'text-secondary'">
                                    Total encaissé : <span x-text="totalPaiements"></span> F
                                    <span x-show="totalPaiements > avoirDisponible">— dépasse l'avoir disponible.</span>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-link" data-bs-dismiss="modal">Annuler</button>
                                <button type="submit" class="btn btn-primary" :disabled="totalPaiements <= 0 || totalPaiements > avoirDisponible">
                                    <i class="bi bi-check-circle me-1"></i>Enregistrer l'encaissement
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    @endcan
@endsection
