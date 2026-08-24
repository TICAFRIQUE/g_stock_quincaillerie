@extends('layouts.app')

@section('title', "Caisse — {$caisse->nom}")

@section('content')
    <div class="d-flex justify-content-between align-items-start mb-1">
        <div>
            <h2 class="h4 mb-1">{{ $caisse->nom }} — {{ $caisse->magasin->nom }}</h2>
            <p class="text-secondary small mb-0">
                Ventes en espèces et mouvements manuels de cette caisse, toutes sessions confondues.
            </p>
        </div>
        <div class="d-flex gap-2 flex-wrap flex-shrink-0">
            <a href="{{ route('comptabilite.caisses.index') }}" class="btn btn-link">
                <i class="bi bi-arrow-left me-1"></i>Retour
            </a>
            <x-bouton-imprimer :pdf-route="route('rapports.mouvements-caisse.pdf', array_merge(request()->query(), ['caisse_id' => $caisse->id]))" />
            <a href="{{ route('rapports.mouvements-caisse.pdf', array_merge(request()->query(), ['caisse_id' => $caisse->id])) }}" class="btn btn-outline-secondary d-print-none">
                <i class="bi bi-file-earmark-pdf me-1"></i>PDF
            </a>
            <a href="{{ route('rapports.mouvements-caisse.excel', array_merge(request()->query(), ['caisse_id' => $caisse->id])) }}" class="btn btn-outline-secondary d-print-none">
                <i class="bi bi-file-earmark-excel me-1"></i>Excel
            </a>
        </div>
    </div>

    @php
        $filtresActifs = collect([
            request('caissier_id') ? 'Caissier : ' . $caissiers->firstWhere('id', (int) request('caissier_id'))?->name : null,
            request('type') ? 'Type : ' . match (request('type')) {
                'sortie' => 'Sortie',
                'vente' => 'Vente / Facture',
                default => 'Entrée',
            } : null,
        ])->filter();
    @endphp
    <p class="text-secondary small mb-3">
        Période : du {{ $debut->format('d/m/Y') }} au {{ $fin->format('d/m/Y') }}
        @if ($filtresActifs->isNotEmpty())
            · {{ $filtresActifs->implode(' · ') }}
        @endif
    </p>

    <form method="GET" action="{{ route('comptabilite.caisses-vente.show', $caisse) }}" class="row g-2 mb-3 d-print-none">
        <div class="col-auto">
            <input type="date" name="debut" value="{{ $debut->toDateString() }}" class="form-control form-control-sm">
        </div>
        <div class="col-auto">
            <input type="date" name="fin" value="{{ $fin->toDateString() }}" class="form-control form-control-sm">
        </div>
        @if ($caissiers->isNotEmpty())
            <div class="col-auto">
                <select name="caissier_id" class="form-select form-select-sm">
                    <option value="">Tous les caissiers</option>
                    @foreach ($caissiers as $caissier)
                        <option value="{{ $caissier->id }}" @selected(request('caissier_id') == $caissier->id)>{{ $caissier->name }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        <div class="col-auto">
            <select name="type" class="form-select form-select-sm">
                <option value="">Tous les types</option>
                <option value="entree" @selected(request('type') === 'entree')>Entrée</option>
                <option value="sortie" @selected(request('type') === 'sortie')>Sortie</option>
                <option value="vente" @selected(request('type') === 'vente')>Vente / Facture</option>
            </select>
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-sm btn-primary">Filtrer</button>
        </div>
        @if (request()->hasAny(['debut', 'fin', 'caissier_id', 'type']))
            <div class="col-auto">
                <a href="{{ route('comptabilite.caisses-vente.show', $caisse) }}" class="btn btn-sm btn-outline-danger" title="Réinitialiser les filtres">
                    <i class="bi bi-x-circle me-1"></i>Réinitialiser
                </a>
            </div>
        @endif
    </form>

    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
            <x-kpi-card label="Mouvements" icon="bi-arrow-left-right" color="primary"
                :value="$nombre" />
        </div>
        <div class="col-6 col-md-3">
            <x-kpi-card label="Ventes en espèces" icon="bi-cash-stack" color="success"
                :value="number_format($totalVentes, 0, ',', ' ') . ' F'" />
        </div>
        <div class="col-6 col-md-3">
            <x-kpi-card label="Total entrées (manuelles)" icon="bi-box-arrow-in-down" color="success"
                :value="number_format($totalEntrees, 0, ',', ' ') . ' F'" />
        </div>
        <div class="col-6 col-md-3">
            <x-kpi-card label="Total sorties" icon="bi-box-arrow-up" color="danger"
                :value="number_format($totalSorties, 0, ',', ' ') . ' F'" />
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Motif</th>
                        <th class="text-end">Montant</th>
                        <th>Auteur</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($mouvements as $mouvement)
                        <tr>
                            <td>{{ \Illuminate\Support\Carbon::parse($mouvement->created_at)->format('d/m/Y H:i') }}</td>
                            <td><span class="badge {{ $mouvement->type_badge }}">{{ $mouvement->type_libelle }}</span></td>
                            <td>{{ $mouvement->motif }}</td>
                            <td class="text-end fw-medium">
                                {{ $mouvement->signe_positif ? '+ ' : '− ' }}{{ number_format($mouvement->montant, 0, ',', ' ') }} F
                            </td>
                            <td>{{ $mouvement->auteur_nom ?? 'Utilisateur supprimé' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-secondary py-4">Aucun mouvement sur cette période.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($mouvements instanceof \Illuminate\Pagination\LengthAwarePaginator)
        <div class="mt-3 d-print-none">
            {{ $mouvements->links() }}
        </div>
    @endif
@endsection
