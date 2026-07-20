@extends('layouts.app')

@section('title', "Rapport de caisse — {$session->caisse->nom}")

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3 d-print-none">
        <h2 class="h4 mb-0">Rapport de caisse</h2>
        <div class="d-flex gap-2">
            <a href="{{ route('sessions.show', $session) }}" class="btn btn-link">
                <i class="bi bi-arrow-left me-1"></i>Retour à la session
            </a>
            <button type="button" class="btn btn-outline-secondary" onclick="window.print()">
                <i class="bi bi-printer me-1"></i>Imprimer
            </button>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="mb-3">
                <div class="fw-bold fs-5">{{ $session->caisse->nom }} — {{ $session->caisse->magasin->nom }}</div>
                <div class="text-secondary small">Caissier : {{ $session->caissier->name }}</div>
                <div class="text-secondary small">Ouverte le {{ $session->date_ouverture->format('d/m/Y à H:i') }}</div>
                @if ($session->date_cloture)
                    <div class="text-secondary small">Clôturée le {{ $session->date_cloture->format('d/m/Y à H:i') }}</div>
                @else
                    <span class="badge text-bg-success">Session en cours</span>
                @endif
            </div>

            <hr>

            <div class="row g-3 mb-3">
                <div class="col-6 col-md-3">
                    <div class="text-secondary small">Fond de caisse</div>
                    <div class="fw-medium">{{ number_format($session->fond_de_caisse, 0, ',', ' ') }} F</div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="text-secondary small">Nombre de ventes</div>
                    <div class="fw-medium">{{ $ventes->count() }}</div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="text-secondary small">Total net</div>
                    <div class="fw-medium">{{ number_format($totalVentes, 0, ',', ' ') }} F</div>
                </div>
            </div>

            @if ($paiementsParMoyen->isNotEmpty())
                <h3 class="h6">Répartition par moyen de paiement</h3>
                <div class="row g-3 mb-3">
                    @foreach ($paiementsParMoyen as $paiement)
                        <div class="col-6 col-md-3">
                            <div class="text-secondary small">{{ $paiement->moyenPaiement->nom }}</div>
                            <div class="fw-medium">{{ number_format($paiement->total, 0, ',', ' ') }} F</div>
                        </div>
                    @endforeach
                </div>
            @endif

            @if ($session->date_cloture)
                <h3 class="h6">Clôture</h3>
                <div class="row g-3 mb-3">
                    <div class="col-6 col-md-3">
                        <div class="text-secondary small">Théorique</div>
                        <div class="fw-medium">{{ number_format($session->fond_de_caisse + $session->total_ventes_especes, 0, ',', ' ') }} F</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="text-secondary small">Compté</div>
                        <div class="fw-medium">{{ number_format($session->montant_compte, 0, ',', ' ') }} F</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="text-secondary small">Écart</div>
                        <div class="fw-medium {{ $session->ecart === 0 ? '' : ($session->ecart > 0 ? 'text-success' : 'text-danger') }}">
                            {{ $session->ecart > 0 ? '+' : '' }}{{ number_format($session->ecart, 0, ',', ' ') }} F
                        </div>
                    </div>
                    @if ($session->clotureePar)
                        <div class="col-6 col-md-3">
                            <div class="text-secondary small">Clôturée par</div>
                            <div class="fw-medium">{{ $session->clotureePar->name }}</div>
                        </div>
                    @endif
                </div>
            @endif

            <h3 class="h6">Liste des ventes</h3>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Numéro</th>
                            <th>Heure</th>
                            <th class="text-end">Total net</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($ventes as $vente)
                            <tr>
                                <td><code>{{ $vente->numero }}</code></td>
                                <td>{{ $vente->created_at->format('H:i') }}</td>
                                <td class="text-end">{{ number_format($vente->total_net, 0, ',', ' ') }} F</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-secondary py-3">Aucune vente pour l'instant.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
