@extends('layouts.app')

@section('title', 'Inventaires')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3 d-print-none">
        <h2 class="h4 mb-0">Inventaires</h2>
        <div class="d-flex gap-2 flex-wrap">
            <x-export-buttons :pdf-route="route('inventaires.pdf', request()->query())" :excel-route="route('inventaires.excel', request()->query())" :tout="true" />
            @can('inventaire.realiser')
                <a href="{{ route('inventaires.create') }}" class="btn btn-primary">Nouvel inventaire</a>
            @endcan
        </div>
    </div>

    <h2 class="h4 mb-3 d-none d-print-block">Inventaires</h2>

    <form method="GET" action="{{ route('inventaires.index') }}" class="row g-2 mb-3 align-items-end d-print-none">
        <div class="col-auto">
            <label for="date_debut" class="form-label small mb-1">Du</label>
            <input type="date" name="date_debut" id="date_debut" class="form-control" value="{{ $dateDebut }}" onchange="this.form.submit()">
        </div>
        <div class="col-auto">
            <label for="date_fin" class="form-label small mb-1">Au</label>
            <input type="date" name="date_fin" id="date_fin" class="form-control" value="{{ $dateFin }}" onchange="this.form.submit()">
        </div>
        <div class="col-auto">
            <label for="statut" class="form-label small mb-1">Statut</label>
            <select name="statut" id="statut" class="form-select" onchange="this.form.submit()">
                <option value="">Tous les statuts</option>
                <option value="brouillon" @selected(request('statut') === 'brouillon')>Brouillon</option>
                <option value="valide" @selected(request('statut') === 'valide')>Validé</option>
            </select>
        </div>
        <div class="col-auto">
            <label for="magasin_id" class="form-label small mb-1">Destination</label>
            <select name="magasin_id" id="magasin_id" class="form-select" onchange="this.form.submit()">
                <option value="">Toutes les destinations</option>
                @foreach ($magasins as $magasin)
                    <option value="{{ $magasin->id }}" @selected(request('magasin_id') == $magasin->id)>{{ $magasin->nom }}</option>
                @endforeach
            </select>
        </div>
        @if (request()->hasAny(['date_debut', 'date_fin', 'statut', 'magasin_id']))
            <div class="col-auto">
                <a href="{{ route('inventaires.index') }}" class="btn btn-outline-danger">
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
                        <x-th-tri champ="date" label="Date" />
                        <th>Destination</th>
                        <x-th-tri champ="statut" label="Statut" />
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
                            <td class="text-end d-print-none">
                                <a href="{{ route('inventaires.show', $inventaire) }}" class="btn btn-sm btn-icon btn-outline-secondary" title="Voir">
                                    <i class="bi bi-eye"></i>
                                    <span class="visually-hidden">Voir</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-secondary py-4">Aucun inventaire pour l'instant.</td>
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
