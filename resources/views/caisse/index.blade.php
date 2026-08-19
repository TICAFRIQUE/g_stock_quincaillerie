@extends('layouts.app')

@section('title', 'Caisse')

@section('content')
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h2 class="h4 mb-1">Caisse</h2>
            <p class="text-secondary small mb-0">
                Entrées et sorties de tiroir. Pour ouvrir une nouvelle caisse, passez par
                <a href="{{ route('sessions.index') }}">Vente → Facture</a>.
            </p>
        </div>
        @can('rapport.voir')
            <a href="{{ route('rapports.mouvements-caisse') }}" class="btn btn-outline-secondary">
                <i class="bi bi-clock-history me-1"></i>Historique complet
            </a>
        @endcan
    </div>

    <div class="row g-3 mb-2">
        <div class="col-6 col-md-4">
            <x-kpi-card label="Solde des tiroirs ouverts" icon="bi-wallet2" color="secondary"
                :value="number_format($soldeTotal, 0, ',', ' ') . ' F'" />
        </div>
        <div class="col-6 col-md-4">
            <x-kpi-card label="Entrées aujourd'hui" icon="bi-box-arrow-in-down" color="success"
                :value="number_format($totalEntrees, 0, ',', ' ') . ' F'" />
        </div>
        <div class="col-6 col-md-4">
            <x-kpi-card label="Sorties aujourd'hui" icon="bi-box-arrow-up" color="danger"
                :value="number_format($totalSorties, 0, ',', ' ') . ' F'" />
        </div>
    </div>

    {{-- Bloc distinct des mouvements de caisse ci-dessus : ce sont des
         chiffres de VENTE du jour, pas du tiroir — seul "Total en caisse"
         (espèces réellement encaissées) alimente vraiment le tiroir. --}}
    <p class="text-secondary small mb-2">Ventes du jour</p>
    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
            <x-kpi-card label="Total ventes" icon="bi-receipt" color="primary"
                :value="number_format($totalVentesAujourdhui, 0, ',', ' ') . ' F'" />
        </div>
        <div class="col-6 col-md-3">
            <x-kpi-card label="Total dû (crédit)" icon="bi-credit-card" :color="$totalDuAujourdhui > 0 ? 'warning' : 'secondary'"
                :value="number_format($totalDuAujourdhui, 0, ',', ' ') . ' F'" />
        </div>
        <div class="col-6 col-md-3">
            <x-kpi-card label="Avoirs appliqués" icon="bi-piggy-bank" color="info"
                :value="number_format($avoirAppliqueAujourdhui, 0, ',', ' ') . ' F'" />
        </div>
        <div class="col-6 col-md-3">
            <x-kpi-card label="Total en caisse" icon="bi-cash-stack" color="success"
                :value="number_format($totalEspecesAujourdhui, 0, ',', ' ') . ' F'" />
        </div>
    </div>

    @if ($sessions->isEmpty())
        <div class="alert alert-warning">
            <i class="bi bi-info-circle me-1"></i>
            @if ($voitTout)
                Aucune caisse ouverte pour l'instant dans votre périmètre.
            @else
                Vous n'avez vous-même aucune caisse ouverte pour l'instant.
            @endif
            @can('caisse.ouvrir')
                Ouvrez-en une depuis <a href="{{ route('sessions.index') }}">Vente → Facture</a> pour commencer à
                enregistrer des mouvements de caisse.
            @endcan
        </div>
    @else
        <div class="row g-3">
            @foreach ($sessions as $session)
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card h-100">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h3 class="h6 mb-0">{{ $session->caisse->nom }}</h3>
                                <span class="badge text-bg-success">Ouverte</span>
                            </div>
                            <p class="text-secondary small mb-3">{{ $session->caisse->magasin->nom }}</p>
                            <p class="small mb-2">
                                Tenue par <strong>{{ $session->caissier->name }}</strong>
                                depuis {{ $session->date_ouverture->format('H:i') }}
                            </p>
                            <p class="mb-3">
                                <span class="text-secondary small">Solde théorique du tiroir</span><br>
                                <span class="fw-semibold fs-5">{{ number_format($soldesTheoriques[$session->id], 0, ',', ' ') }} F</span>
                            </p>
                            <a href="{{ route('caisse.show', $session) }}" class="btn btn-outline-primary mt-auto">
                                <i class="bi bi-arrow-right-circle me-1"></i>Se connecter à cette caisse
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endsection
