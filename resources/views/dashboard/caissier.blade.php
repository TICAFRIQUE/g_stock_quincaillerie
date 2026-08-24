@extends('layouts.app')

@section('title', 'Tableau de bord')

@section('content')
    <div class="mb-4">
        <h2 class="h4">Bonjour, {{ $utilisateur->name }} 👋</h2>
    </div>

    @if (! $session)
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bi bi-cash-register fs-1 text-secondary"></i>
                <p class="mt-2 mb-3">Vous n'avez pas de session ouverte. Ouvrez une session sur une caisse libre de votre magasin pour commencer à vendre.</p>
                @can('caisse.ouvrir')
                    <a href="{{ route('sessions.index') }}" class="btn btn-primary">Aller aux caisses</a>
                @endcan
            </div>
        </div>
    @else
        <x-alerte-session-ancienne :session="$session" />

        <div class="d-flex justify-content-between align-items-center mb-3">
            <p class="text-secondary mb-0">Session ouverte sur {{ $session->caisse->nom }} — {{ $session->caisse->magasin->nom }}</p>
            <div class="d-flex gap-2">
                @can('vente.creer')
                    <a href="{{ route('ventes.create', $session) }}" class="btn btn-primary">
                        <i class="bi bi-cart-plus me-1"></i>Vendre
                    </a>
                @endcan
                <a href="{{ route('sessions.show', $session) }}" class="btn btn-outline-secondary">Voir la session</a>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-6 col-md-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-secondary small">Ventes</div>
                        <div class="fs-4 fw-medium">{{ $nombreVentes }}</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-secondary small">Chiffre d'affaires</div>
                        <div class="fs-4 fw-medium">{{ number_format($totalVentes, 0, ',', ' ') }} F</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-secondary small">Panier moyen</div>
                        <div class="fs-4 fw-medium">{{ number_format($panierMoyen, 0, ',', ' ') }} F</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-secondary small">Ventes en attente</div>
                        <div class="fs-4 fw-medium">{{ $venteEnAttenteCount }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Ces trois chiffres décomposent le chiffre d'affaires ci-dessus :
             dû (crédit encore ouvert), avoir (compensé, jamais encaissé) et
             espèces (réellement dans le tiroir) — volontairement séparés
             pour ne jamais laisser croire que le CA est de l'argent en caisse. --}}
        <p class="text-secondary small mb-2">Décomposition</p>
        <div class="row g-3 mb-3">
            <div class="col-6 col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-secondary small">Total dû (crédit)</div>
                        <div class="fs-4 fw-medium {{ $totalDu > 0 ? 'text-warning-emphasis' : '' }}">{{ number_format($totalDu, 0, ',', ' ') }} F</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-secondary small">Avoirs appliqués</div>
                        <div class="fs-4 fw-medium">{{ number_format($avoirApplique, 0, ',', ' ') }} F</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-secondary small">Total en caisse</div>
                        <div class="fs-4 fw-medium">{{ number_format($totalEspeces, 0, ',', ' ') }} F</div>
                        <div class="small text-secondary fst-italic">Espèces réellement encaissées</div>
                    </div>
                </div>
            </div>
        </div>

        @if ($venteEnAttenteCount > 0)
            <div class="alert alert-warning d-flex justify-content-between align-items-center">
                <span><i class="bi bi-hourglass-split me-2"></i>{{ $venteEnAttenteCount }} panier(s) en attente.</span>
                <a href="{{ route('ventes-en-attente.index', $session) }}" class="btn btn-sm btn-warning">Voir</a>
            </div>
        @endif

        <div class="card">
            <div class="card-body">
                <h3 class="h6">Encaissements par moyen de paiement</h3>
                @if ($parMoyen->isEmpty())
                    <p class="text-secondary small fst-italic mb-0">Aucune vente pour l'instant sur cette session.</p>
                @else
                    <table class="table table-sm mb-0">
                        @foreach ($parMoyen as $moyen)
                            <tr>
                                <td>{{ $moyen->nom }}</td>
                                <td class="text-end">{{ number_format($moyen->total, 0, ',', ' ') }} F</td>
                            </tr>
                        @endforeach
                    </table>
                @endif
            </div>
        </div>
    @endif
@endsection
