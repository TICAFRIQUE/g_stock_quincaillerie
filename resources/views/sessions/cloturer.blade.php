@extends('layouts.app')

@section('title', 'Clôturer la session')

@section('content')
    <h2 class="h4 mb-3">Clôturer la session — {{ $session->caisse->nom }}</h2>

    <div class="row g-3 mb-3" style="max-width: 720px;">
        <div class="col-6 col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-secondary small">Fond de caisse</div>
                    <div class="fs-5 fw-medium">{{ montant($session->fond_de_caisse) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-secondary small">Total de tous</div>
                    <div class="fs-5 fw-medium">{{ montant($totalVentes) }}</div>
                </div>
            </div>
        </div>
    </div>

    @if ($paiementsParMoyen->isNotEmpty())
        <div class="card mb-3" style="max-width: 720px;">
            <div class="card-body">
                <h3 class="h6">Répartition par moyen de paiement</h3>
                <div class="row g-3">
                    @foreach ($paiementsParMoyen as $paiement)
                        <div class="col-6 col-md-3">
                            <div class="text-secondary small">{{ $paiement->moyenPaiement->nom }}</div>
                            <div class="fw-medium">{{ montant($paiement->total) }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <div class="card mx-auto" style="max-width: 480px;"
         x-data="{
             montantCompte: {{ (int) old('montant_compte', $theorique) }},
             theorique: {{ (int) $theorique }},
             get ecart() { return (Number(this.montantCompte) || 0) - this.theorique; },
             declencherCloture(event) {
                 const form = document.getElementById('formCloture');
                 if (!form.checkValidity()) {
                     form.classList.add('was-validated');
                     form.reportValidity();
                     return;
                 }
                 window.bootstrap.Modal.getOrCreateInstance(document.getElementById('confirmActionModal')).show(event.currentTarget);
             },
         }">
        <div class="card-body">
            <p class="text-secondary small mb-1">Seules les espèces sont comptées dans le tiroir.</p>
            <p class="mb-1">Théorique (fond de caisse + ventes et règlements espèces + entrées − sorties) : <strong>{{ montant($theorique) }}</strong></p>
            @if ($detailTheorique['entrees'] > 0 || $detailTheorique['sorties'] > 0)
                <p class="text-secondary small mb-3">
                    dont entrées de caisse : {{ montant($detailTheorique['entrees']) }},
                    sorties de caisse : − {{ montant($detailTheorique['sorties']) }}
                </p>
            @else
                <div class="mb-3"></div>
            @endif

            <form id="formCloture" method="POST" action="{{ route('sessions.cloturer', $session) }}">
                @csrf

                <div class="mb-2">
                    <label for="montant_compte" class="form-label">Montant compté dans le tiroir (F CFA)<span class="required-marker">*</span></label>
                    <input type="number" name="montant_compte" id="montant_compte"
                           x-model.number="montantCompte"
                           class="form-control @error('montant_compte') is-invalid @enderror"
                           min="0" required autofocus>
                    @error('montant_compte') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-4 small" :class="ecart === 0 ? 'text-success' : 'text-danger'">
                    <template x-if="ecart === 0">
                        <span><i class="bi bi-check-circle me-1"></i>Conforme au théorique.</span>
                    </template>
                    <template x-if="ecart !== 0">
                        <span><i class="bi bi-exclamation-triangle-fill me-1"></i>Écart : <span x-text="(ecart > 0 ? '+' : '') + ecart"></span> F</span>
                    </template>
                </div>

                <button type="button" class="btn btn-primary"
                        @click="declencherCloture($event)"
                        data-form-id="formCloture"
                        :data-message="ecart === 0
                            ? 'Clôturer cette session ? Cette action est irréversible.'
                            : 'Écart de ' + (ecart > 0 ? '+' : '') + ecart + ' F par rapport au théorique. Clôturer quand même ?'"
                        data-button-label="Clôturer" data-button-class="btn-primary">
                    <i class="bi bi-lock me-1"></i>Clôturer
                </button>
                <a href="{{ route('sessions.show', $session) }}" class="btn btn-link">Annuler</a>
            </form>
        </div>
    </div>
@endsection
