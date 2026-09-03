@extends('layouts.app')

@section('title', 'Casse / pertes')

@section('content')
    <h2 class="h4 mb-1">Casse / pertes</h2>
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

    <form method="GET" action="{{ route('rapports.casse') }}" class="row g-2 mb-3 d-print-none">
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
                <x-bouton-reinitialiser :route="route('rapports.casse')" />
            </div>
        @endif
        <div class="col-auto ms-auto">
            <x-export-buttons :pdf-route="route('rapports.casse.pdf', request()->query())" :excel-route="route('rapports.casse.excel', request()->query())" />
        </div>
    </form>

    <div class="card mb-3" style="max-width: 320px;">
        <div class="card-body">
            <div class="text-secondary small">Total pièces perdues</div>
            <div class="fs-4 fw-medium text-danger">{{ $totalPieces }}</div>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Produit</th>
                        <th>Magasin</th>
                        <th>Pièces perdues</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($lignes as $ligne)
                        <tr>
                            <td>{{ $ligne->nom }} <code class="small">{{ $ligne->sku }}</code></td>
                            <td>{{ $ligne->magasin_nom }}</td>
                            <td class="text-danger fw-medium">{{ $ligne->pieces_perdues }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center text-secondary py-4">Aucune casse sur cette période.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
