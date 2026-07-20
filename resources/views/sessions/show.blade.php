@extends('layouts.app')

@section('title', "Session — {$session->caisse->nom}")

@section('content')
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-2 mb-3">
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
            @can('rapport.voir')
                @if ($sessionsAujourdhui > 0)
                    <p class="small mb-0">
                        <a href="{{ route('rapports.ventes', ['caisse_id' => $session->caisse_id, 'debut' => $session->date_ouverture->toDateString(), 'fin' => $session->date_ouverture->toDateString()]) }}">
                            <i class="bi bi-clock-history me-1"></i>Voir les autres sessions de cette caisse aujourd'hui ({{ $sessionsAujourdhui }})
                        </a>
                    </p>
                @endif
            @endcan
        </div>

        <div class="d-flex flex-wrap gap-2">
            @if ($session->date_cloture)
                <a href="{{ route('sessions.rapport', $session) }}" class="btn btn-outline-secondary">
                    <i class="bi bi-file-earmark-text me-1"></i>Rapport de caisse
                </a>
            @endif
            @if (! $session->date_cloture)
                @can('ventenattente.gerer')
                    <a href="{{ route('ventes-en-attente.index', $session) }}" class="btn btn-outline-warning position-relative">
                        <i class="bi bi-hourglass-split me-1"></i>Ventes en attente
                        @if ($session->vente_en_attentes_count > 0)
                            <span class="badge rounded-pill text-bg-warning ms-1">{{ $session->vente_en_attentes_count }}</span>
                        @endif
                    </a>
                @endcan
                @can('vente.creer')
                    <a href="{{ route('ventes.create', $session) }}" class="btn btn-primary">
                        <i class="bi bi-cart-plus me-1"></i>Vendre
                    </a>
                @endcan
                @can('caisse.cloturer')
                    @if ($session->vente_en_attentes_count > 0)
                        <button type="button" class="btn btn-outline-secondary disabled" disabled
                                title="Impossible de clôturer : {{ $session->vente_en_attentes_count }} vente(s) en attente à finaliser ou annuler d'abord.">
                            <i class="bi bi-lock me-1"></i>Clôturer
                        </button>
                    @else
                        <a href="{{ route('sessions.cloturer.form', $session) }}" class="btn btn-outline-secondary">
                            <i class="bi bi-lock me-1"></i>Clôturer
                        </a>
                    @endif
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
            <x-kpi-card label="Fond de caisse" icon="bi-cash-stack" color="primary"
                :value="number_format($session->fond_de_caisse, 0, ',', ' ') . ' F'" />
        </div>
        <div class="col-6 col-md-3">
            <x-kpi-card label="Total ventes" icon="bi-receipt" color="info"
                :value="$session->ventes_count" />
        </div>
        <div class="col-6 col-md-3">
            <x-kpi-card label="Ventes en attente" icon="bi-hourglass-split" color="warning"
                :value="$session->vente_en_attentes_count" />
        </div>
        <div class="col-6 col-md-3">
            <x-kpi-card label="Total net en caisse" icon="bi-graph-up-arrow" color="success"
                :value="number_format($totalVentes, 0, ',', ' ') . ' F'" />
        </div>
    </div>

    @if ($paiementsParMoyen->isNotEmpty())
        <div class="card mb-3">
            <div class="card-body">
                <h3 class="h6">Répartition par moyen de paiement</h3>
                <div class="row g-3">
                    @foreach ($paiementsParMoyen as $paiement)
                        <div class="col-6 col-md-3">
                            <div class="text-secondary small">{{ $paiement->moyenPaiement->nom }}</div>
                            <div class="fw-medium">{{ number_format($paiement->total, 0, ',', ' ') }} F</div>
                        </div>
                    @endforeach
                </div>
            </div>
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

    <x-recherche-form :action="route('sessions.show', $session)" placeholder="Numéro de vente…" />

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <x-th-tri champ="numero" label="Numéro" />
                        <x-th-tri champ="created_at" label="Date et heure" />
                        <x-th-tri champ="total_net" label="Total" />
                        <th>Statut</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($ventes as $vente)
                        <tr>
                            <td><code>{{ $vente->numero }}</code></td>
                            <td>{{ $vente->created_at->format('d/m/Y H:i') }}</td>
                            <td>{{ number_format($vente->total_net, 0, ',', ' ') }} F</td>
                            <td><span class="badge text-bg-success">Validée</span></td>
                            <td class="text-end">
                                <a href="{{ route('ventes.ticket', $vente) }}" class="btn btn-sm btn-icon btn-outline-secondary" title="Détail de la vente">
                                    <i class="bi bi-eye"></i>
                                    <span class="visually-hidden">Détail</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-secondary py-4">Aucune vente pour l'instant.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3 d-flex justify-content-between align-items-center">
        <a href="{{ route('sessions.index') }}" class="btn btn-link ps-0">Retour aux caisses</a>
        {{ $ventes->links() }}
    </div>
@endsection
