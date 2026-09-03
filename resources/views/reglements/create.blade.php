@extends('layouts.app')

@section('title', 'Règlement client')

@section('content')
    <div x-data="reglementApp()">
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
            <div class="card mx-auto" style="max-width: 640px;">
                <div class="card-body">
                    <form id="formReglement" method="POST" action="{{ route('reglements.store', $session) }}">
                        @csrf

                        <div class="mb-3">
                            <label for="client_id" class="form-label">Client<span class="required-marker">*</span></label>
                            <select name="client_id" id="client_id" class="form-select" x-model="clientId" required>
                                <option value="">— Choisir un client —</option>
                                @foreach ($clients as $c)
                                    <option value="{{ $c->id }}" data-solde="{{ $c->solde }}">
                                        {{ $c->nom }} — doit {{ montant($c->solde) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-2 small text-secondary" x-show="clientId" x-cloak>
                            Dette actuelle : <span x-text="soldeClient" class="fw-medium"></span> F
                        </div>

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
                        <button type="button" class="btn btn-sm btn-outline-primary mb-3" @click="ajouterPaiement()">
                            <i class="bi bi-plus-lg"></i> Ajouter un moyen de paiement
                        </button>

                        <div class="mb-3 small" :class="totalPaiements > 0 && totalPaiements > soldeClient ? 'text-danger' : 'text-secondary'">
                            Total réglé : <span x-text="totalPaiements"></span> F
                            <span x-show="clientId && totalPaiements > soldeClient">— dépasse la dette actuelle.</span>
                        </div>

                        <button type="submit" class="btn btn-primary" :disabled="!clientId || totalPaiements <= 0">
                            <i class="bi bi-check-circle me-1"></i>Enregistrer le règlement
                        </button>
                        <a href="{{ route('sessions.show', $session) }}" class="btn btn-link">Annuler</a>
                    </form>
                </div>
            </div>
        @endif
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            window.initSelect2('#client_id', { placeholder: '— Choisir un client —' });
        });

        function reglementApp() {
            return {
                clientId: '',
                paiements: [{ moyen_paiement_id: '', montant: null }],

                get soldeClient() {
                    const option = document.querySelector(`#client_id option[value="${this.clientId}"]`);
                    return option ? Number(option.dataset.solde) : 0;
                },

                get totalPaiements() {
                    return this.paiements.reduce((somme, p) => somme + (Number(p.montant) || 0), 0);
                },

                ajouterPaiement() {
                    this.paiements.push({ moyen_paiement_id: '', montant: null });
                },

                retirerPaiement(index) {
                    if (this.paiements.length > 1) this.paiements.splice(index, 1);
                },
            };
        }
    </script>
@endpush
