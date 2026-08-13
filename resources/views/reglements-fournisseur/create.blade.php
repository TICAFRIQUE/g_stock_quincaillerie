@extends('layouts.app')

@section('title', 'Règlement fournisseur')

@section('content')
    <div x-data="reglementFournisseurApp()">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-2 mb-3">
            <div>
                <h2 class="h4 mb-0">Règlement fournisseur — {{ $fournisseur->nom }}</h2>
                <p class="text-secondary small mb-0">Dette actuelle : {{ number_format($solde, 0, ',', ' ') }} F</p>
            </div>
            <a href="{{ route('fournisseurs.show', $fournisseur) }}" class="btn btn-link">
                <i class="bi bi-arrow-left me-1"></i>Retour à la fiche fournisseur
            </a>
        </div>

        <div class="card" style="max-width: 640px;">
            <div class="card-body">
                <form method="POST" action="{{ route('reglements-fournisseur.store', $fournisseur) }}">
                    @csrf

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

                    <div class="mb-3 small" :class="totalPaiements > 0 && totalPaiements > {{ $solde }} ? 'text-danger' : 'text-secondary'">
                        Total réglé : <span x-text="totalPaiements"></span> F
                        <span x-show="totalPaiements > {{ $solde }}">— dépasse la dette actuelle.</span>
                    </div>

                    <button type="submit" class="btn btn-primary" :disabled="totalPaiements <= 0">
                        <i class="bi bi-check-circle me-1"></i>Enregistrer le règlement
                    </button>
                    <a href="{{ route('fournisseurs.show', $fournisseur) }}" class="btn btn-link">Annuler</a>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function reglementFournisseurApp() {
            return {
                paiements: [{ moyen_paiement_id: '', montant: null }],

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
