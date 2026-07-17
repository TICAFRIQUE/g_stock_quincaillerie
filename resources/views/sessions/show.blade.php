@extends('layouts.app')

@section('title', "Session — {$session->caisse->nom}")

@section('content')
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h2 class="h4 mb-1">{{ $session->caisse->nom }} — {{ $session->caisse->magasin->nom }}</h2>
            <p class="text-secondary small mb-0">
                Ouverte par {{ $session->caissier->name }} le {{ $session->date_ouverture->format('d/m/Y à H:i') }}
                @if ($session->date_cloture)
                    <span class="badge text-bg-secondary ms-2">Clôturée</span>
                @else
                    <span class="badge text-bg-success ms-2">Ouverte</span>
                @endif
            </p>
        </div>

        <div class="d-flex gap-2">
            @if (! $session->date_cloture)
                @can('vente.creer')
                    <a href="{{ route('ventes.create', $session) }}" class="btn btn-primary">
                        <i class="bi bi-cart-plus me-1"></i>Vendre
                    </a>
                @endcan
                @can('caisse.cloturer')
                    <a href="{{ route('sessions.cloturer.form', $session) }}" class="btn btn-outline-secondary">
                        <i class="bi bi-lock me-1"></i>Clôturer
                    </a>
                @endcan
            @elseif (! $session->date_fermeture)
                @can('caisse.fermer')
                    <x-confirm-button :action="route('sessions.fermer', $session)"
                        message="Fermer cette session ? La caisse redeviendra libre pour une nouvelle ouverture."
                        button-label="Fermer la session" button-class="btn-outline-danger" icon="bi-door-closed" />
                @endcan
            @endif
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-secondary small">Fond de caisse</div>
                    <div class="fs-5 fw-medium">{{ number_format($session->fond_de_caisse, 0, ',', ' ') }} F</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-secondary small">Ventes</div>
                    <div class="fs-5 fw-medium">{{ $session->ventes_count }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-secondary small">Total encaissé</div>
                    <div class="fs-5 fw-medium">{{ number_format($totalVentes, 0, ',', ' ') }} F</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-secondary small">Dont espèces</div>
                    <div class="fs-5 fw-medium">{{ number_format($totalEspeces, 0, ',', ' ') }} F</div>
                </div>
            </div>
        </div>
    </div>

    @if ($session->venteEnAttentes_count > 0)
        <div class="alert alert-warning d-flex justify-content-between align-items-center">
            <span><i class="bi bi-hourglass-split me-2"></i>{{ $session->venteEnAttentes_count }} vente(s) en attente sur cette session.</span>
            <a href="{{ route('ventes-en-attente.index', $session) }}" class="btn btn-sm btn-warning">Voir</a>
        </div>
    @endif

    @if ($session->date_cloture)
        <div class="card mb-3">
            <div class="card-body">
                <h3 class="h6">Clôture</h3>
                <div class="row g-3">
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
                </div>
            </div>
        </div>
    @endif

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Numéro</th>
                        <th>Heure</th>
                        <th>Total net</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($session->ventes as $vente)
                        <tr>
                            <td><code>{{ $vente->numero }}</code></td>
                            <td>{{ $vente->created_at->format('H:i') }}</td>
                            <td>{{ number_format($vente->total_net, 0, ',', ' ') }} F</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center text-secondary py-4">Aucune vente pour l'instant.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        <a href="{{ route('sessions.index') }}" class="btn btn-link ps-0">Retour aux caisses</a>
    </div>
@endsection
