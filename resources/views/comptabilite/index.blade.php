@extends('layouts.app')

@section('title', 'Trésorerie — Caisses')

@section('content')
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h2 class="h4 mb-1">Trésorerie — Caisses</h2>
            <p class="text-secondary small mb-0">
                Caisse Générale, comptes bancaires/autres, et toutes les caisses de vente.
                La Caisse Générale n'a rien à voir avec les caisses des caissiers ci-dessous : elle reçoit
                automatiquement la recette de chaque session clôturée.
            </p>
        </div>
        <div class="d-flex gap-2 flex-shrink-0">
            <a href="{{ route('rapports.tresorerie') }}" class="btn btn-outline-secondary">
                <i class="bi bi-clock-history me-1"></i>Historique complet
            </a>
            @can('tresorerie.gerer')
                <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#nouveauCompteModal">
                    <i class="bi bi-plus-lg me-1"></i>Nouveau compte
                </button>
            @endcan
        </div>
    </div>

    <p class="text-secondary small mb-2">Trésorerie</p>
    <div class="row g-3 mb-4">
        @foreach ($comptes as $compte)
            <div class="col-12 col-md-6 col-lg-4">
                <a href="{{ route('comptabilite.caisses.show', $compte) }}" class="text-decoration-none text-body">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h3 class="h6 mb-0">{{ $compte->nom }}</h3>
                                @if ($compte->type === 'caisse_generale')
                                    <span class="badge text-bg-primary">Caisse Générale</span>
                                @elseif ($compte->type === 'banque')
                                    <span class="badge text-bg-info">Banque</span>
                                @else
                                    <span class="badge text-bg-secondary">Autre</span>
                                @endif
                            </div>
                            <div class="text-secondary small">Solde</div>
                            <div class="fs-5 fw-semibold">{{ number_format($soldesComptes[$compte->id], 0, ',', ' ') }} F</div>
                        </div>
                    </div>
                </a>
            </div>
        @endforeach
    </div>

    <p class="text-secondary small mb-2">Caisses des caissiers</p>
    @if ($caisses->isEmpty())
        <p class="text-secondary small fst-italic">Aucune caisse pour l'instant.</p>
    @else
        <div class="row g-3">
            @foreach ($caisses as $caisse)
                @php $sessionOuverte = $caisse->sessionCaisses->first(); @endphp
                <div class="col-12 col-md-6 col-lg-4">
                    <a href="{{ route('comptabilite.caisses-vente.show', $caisse) }}" class="text-decoration-none text-body">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h3 class="h6 mb-0">{{ $caisse->nom }}</h3>
                                    @if ($sessionOuverte)
                                        <span class="badge text-bg-success">Ouverte</span>
                                    @else
                                        <span class="badge text-bg-secondary">Libre</span>
                                    @endif
                                </div>
                                <p class="text-secondary small mb-2">
                                    {{ $caisse->magasin->nom }}
                                    @if ($sessionOuverte)
                                        — {{ $sessionOuverte->caissier->name }}
                                    @endif
                                </p>
                                @if ($sessionOuverte)
                                    <div class="text-secondary small">Solde théorique du tiroir</div>
                                    <div class="fs-5 fw-semibold">{{ number_format($soldesTheoriques[$caisse->id], 0, ',', ' ') }} F</div>
                                @else
                                    <div class="text-secondary small fst-italic">Aucune session ouverte</div>
                                @endif
                            </div>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
    @endif

    @can('tresorerie.gerer')
        <div class="modal fade" id="nouveauCompteModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="{{ route('comptabilite.comptes.store') }}">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">Nouveau compte</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="nom" class="form-label">Nom<span class="required-marker">*</span></label>
                                <input type="text" name="nom" id="nom" class="form-control" placeholder="Ex. BICICI, Orange Money…" required>
                            </div>
                            <div class="mb-0">
                                <label for="type" class="form-label">Type<span class="required-marker">*</span></label>
                                <select name="type" id="type" class="form-select" required>
                                    <option value="banque">Banque</option>
                                    <option value="autre">Autre</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-link" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-primary">Créer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan
@endsection
