@extends('layouts.app')

@section('title', 'Règlement client')

@section('content')
    <div class="mb-3">
        <div class="d-flex align-items-center gap-2">
            <x-bouton-retour :route="route('sessions.show', $session)" />
            <h2 class="h4 mb-0">Règlement client — {{ $session->caisse->nom }}</h2>
        </div>
        <p class="text-secondary small mb-0 ms-5 ps-1">{{ $session->caisse->magasin->nom }}</p>
    </div>

    @if ($clients->isEmpty())
        <div class="alert alert-secondary">Aucun client n'a de dette en cours pour le moment.</div>
    @else
        <p class="text-secondary small">
            Cliquez sur un client pour dérouler ses factures ouvertes et régler chacune séparément — chaque
            règlement est rattaché à sa facture, sans quoi le montant réglé/reste dû ne se mettrait jamais à jour.
        </p>

        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Client</th>
                            <th class="text-end">Dette totale</th>
                            <th class="text-end">Factures ouvertes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($clients as $client)
                            <tr>
                                <td>
                                    <button type="button" class="btn btn-link p-0 text-decoration-none fw-medium"
                                            data-bs-toggle="collapse" data-bs-target="#factures-client-{{ $client->id }}">
                                        <i class="bi bi-chevron-down me-1"></i>{{ $client->nom }}
                                    </button>
                                </td>
                                <td class="text-end fw-medium text-danger">{{ montant($client->solde) }}</td>
                                <td class="text-end">{{ $client->facturesOuvertes->count() }}</td>
                            </tr>
                            <tr class="collapse" id="factures-client-{{ $client->id }}">
                                <td colspan="3" class="p-0">
                                    <table class="table table-sm mb-0">
                                        <thead>
                                            <tr class="small text-secondary">
                                                <th class="ps-4">Facture</th>
                                                <th>Date</th>
                                                <th class="text-end">Total</th>
                                                <th class="text-end">Réglé</th>
                                                <th class="text-end">Reste dû</th>
                                                <th class="text-end">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($client->facturesOuvertes as $vente)
                                                <tr>
                                                    <td class="ps-4"><code>{{ $vente->numero }}</code></td>
                                                    <td>{{ $vente->created_at->format('d/m/Y') }}</td>
                                                    <td class="text-end">{{ montant($vente->total_net) }}</td>
                                                    <td class="text-end text-success">{{ montant($vente->montantRegle()) }}</td>
                                                    <td class="text-end text-danger fw-medium">{{ montant($vente->soldeDuReel()) }}</td>
                                                    <td class="text-end">
                                                        <a href="{{ route('ventes.ticket', $vente) }}" class="btn btn-sm btn-icon btn-outline-secondary" title="Détail de la vente">
                                                            <i class="bi bi-eye"></i>
                                                            <span class="visually-hidden">Détail</span>
                                                        </a>
                                                        <button type="button" class="btn btn-sm btn-icon btn-outline-reglement" title="Régler cette facture"
                                                                data-bs-toggle="modal" data-bs-target="#reglerClientModal"
                                                                data-client="{{ $client->id }}" data-client-nom="{{ $client->nom }}"
                                                                data-vente="{{ $vente->id }}" data-numero="{{ $vente->numero }}"
                                                                data-montant="{{ $vente->soldeDuReel() }}">
                                                            <i class="bi bi-cash-coin"></i>
                                                            <span class="visually-hidden">Régler</span>
                                                        </button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="modal fade" id="reglerClientModal" tabindex="-1" aria-hidden="true"
             x-data="{
                 paiements: [{ moyen_paiement_id: '', montant: null }],
                 clientId: null,
                 clientNom: null,
                 venteId: null,
                 venteNumero: null,
                 detteAffichee: 0,
                 get totalPaiements() { return this.paiements.reduce((total, p) => total + (Number(p.montant) || 0), 0); },
                 ajouterPaiement() { this.paiements.push({ moyen_paiement_id: '', montant: null }); },
                 retirerPaiement(index) { if (this.paiements.length > 1) this.paiements.splice(index, 1); },
             }"
             x-init="$el.addEventListener('show.bs.modal', (event) => {
                 clientId = event.relatedTarget?.dataset.client || null;
                 clientNom = event.relatedTarget?.dataset.clientNom || null;
                 venteId = event.relatedTarget?.dataset.vente || null;
                 venteNumero = event.relatedTarget?.dataset.numero || null;
                 const montant = event.relatedTarget?.dataset.montant ? Number(event.relatedTarget.dataset.montant) : 0;
                 paiements = [{ moyen_paiement_id: '', montant: montant }];
                 detteAffichee = montant;
             })">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="{{ route('reglements.store', $session) }}">
                        @csrf
                        <input type="hidden" name="client_id" :value="clientId">
                        <input type="hidden" name="vente_id" :value="venteId">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="bi bi-cash-coin text-reglement me-2"></i>Régler <span x-text="clientNom"></span>
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                        </div>
                        <div class="modal-body">
                            <p class="small text-secondary">
                                Reste dû sur la facture <strong x-text="venteNumero"></strong> :
                                <strong x-text="detteAffichee.toLocaleString('fr-FR') + ' ' + window.DEVISE_ABREVIATION"></strong>
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
                                        <input type="number" :name="'paiements['+index+'][montant]'" x-model.number="paiement.montant"
                                               min="1" :max="detteAffichee" class="form-control form-control-sm" placeholder="Montant" required>
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

                            <div class="mt-3 small" :class="totalPaiements > detteAffichee ? 'text-danger' : 'text-secondary'">
                                Total réglé : <span x-text="totalPaiements.toLocaleString('fr-FR')"></span> <span x-text="window.DEVISE_ABREVIATION"></span>
                                <span x-show="totalPaiements > detteAffichee">— dépasse le reste dû, corrigez le montant.</span>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-link" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-reglement" :disabled="!clientId || !venteId || totalPaiements <= 0 || totalPaiements > detteAffichee">
                                <i class="bi bi-check-circle me-1"></i>Enregistrer le règlement
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endsection
