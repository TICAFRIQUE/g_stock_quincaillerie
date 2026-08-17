@extends('layouts.app')

@section('title', 'Historique des inventaires')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-1">
        <h2 class="h4 mb-0">Historique des inventaires</h2>
        <div class="d-flex gap-2 flex-wrap">
            <x-bouton-imprimer tout />
            <a href="{{ route('rapports.inventaires.pdf', request()->query()) }}" class="btn btn-outline-secondary d-print-none">
                <i class="bi bi-file-earmark-pdf me-1"></i>PDF
            </a>
            <a href="{{ route('rapports.inventaires.excel', request()->query()) }}" class="btn btn-outline-secondary d-print-none">
                <i class="bi bi-file-earmark-excel me-1"></i>Excel
            </a>
        </div>
    </div>
    @php
        $magasinIdActif = auth()->user()->magasin_id ?: request('magasin_id');
        $filtresActifs = collect([
            $magasinIdActif ? 'Magasin : ' . $magasins->firstWhere('id', (int) $magasinIdActif)?->nom : null,
        ])->filter();
    @endphp
    <p class="text-secondary small mb-3">
        Période : du {{ $debut->format('d/m/Y') }} au {{ $fin->format('d/m/Y') }}
        @if ($filtresActifs->isNotEmpty())
            · {{ $filtresActifs->implode(' · ') }}
        @endif
    </p>

    <form method="GET" action="{{ route('rapports.inventaires') }}" class="row g-2 mb-3 d-print-none">
        <div class="col-auto">
            <input type="date" name="debut" value="{{ $debut->toDateString() }}" class="form-control form-control-sm">
        </div>
        <div class="col-auto">
            <input type="date" name="fin" value="{{ $fin->toDateString() }}" class="form-control form-control-sm">
        </div>
        @unless (auth()->user()->magasin_id)
            <div class="col-auto">
                <select name="magasin_id" class="form-select form-select-sm">
                    <option value="">Tous les magasins</option>
                    @foreach ($magasins as $magasin)
                        <option value="{{ $magasin->id }}" @selected(request('magasin_id') == $magasin->id)>{{ $magasin->nom }}</option>
                    @endforeach
                </select>
            </div>
        @endunless
        <div class="col-auto">
            <button type="submit" class="btn btn-sm btn-primary">Filtrer</button>
        </div>
        @if (request()->hasAny(['debut', 'fin', 'magasin_id']))
            <div class="col-auto">
                <a href="{{ route('rapports.inventaires') }}" class="btn btn-sm btn-outline-danger" title="Réinitialiser les filtres">
                    <i class="bi bi-x-circle me-1"></i>Réinitialiser
                </a>
            </div>
        @endif
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Magasin</th>
                        <th>Statut</th>
                        <th>Lignes comptées</th>
                        <th>Écart net (pièces)</th>
                        <th>Réalisé par</th>
                        <th class="text-end d-print-none">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($inventaires as $inventaire)
                        <tr>
                            <td>{{ $inventaire->date->format('d/m/Y') }}</td>
                            <td>{{ $inventaire->magasin->nom }}</td>
                            <td>
                                @if ($inventaire->statut === 'valide')
                                    <span class="badge text-bg-success">Validé</span>
                                @else
                                    <span class="badge text-bg-secondary">Brouillon</span>
                                @endif
                            </td>
                            <td>{{ $inventaire->lignes_count }}</td>
                            <td class="{{ ($inventaire->ecart_total ?? 0) == 0 ? '' : (($inventaire->ecart_total ?? 0) > 0 ? 'text-success' : 'text-danger') }} fw-medium">
                                {{ ($inventaire->ecart_total ?? 0) > 0 ? '+' : '' }}{{ $inventaire->ecart_total ?? 0 }}
                            </td>
                            <td>{{ $inventaire->auteur->name }}</td>
                            <td class="text-end d-print-none">
                                <a href="{{ route('inventaires.show', $inventaire) }}" class="btn btn-sm btn-icon btn-outline-secondary" title="Détail de l'inventaire">
                                    <i class="bi bi-eye"></i>
                                    <span class="visually-hidden">Détail</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-secondary py-4">Aucun inventaire sur cette période.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($inventaires instanceof \Illuminate\Pagination\LengthAwarePaginator)
        <div class="mt-3 d-print-none">
            {{ $inventaires->links() }}
        </div>
    @endif
@endsection
