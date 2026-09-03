@extends('layouts.app')

@section('title', 'Rapport de marge')

@section('content')
    <h2 class="h4 mb-1">Rapport de marge</h2>
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

    <form method="GET" action="{{ route('rapports.marge') }}" class="row g-2 mb-3 d-print-none">
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
                <x-bouton-reinitialiser :route="route('rapports.marge')" />
            </div>
        @endif
        <div class="col-auto ms-auto">
            <x-export-buttons :pdf-route="route('rapports.marge.pdf', request()->query())" :excel-route="route('rapports.marge.excel', request()->query())" />
        </div>
    </form>

    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
            <div class="card h-100"><div class="card-body">
                <div class="text-secondary small">Ventes</div>
                <div class="fs-5 fw-medium">{{ montant($totalVentes) }}</div>
            </div></div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card h-100"><div class="card-body">
                <div class="text-secondary small">Coût</div>
                <div class="fs-5 fw-medium">{{ montant($totalCout) }}</div>
            </div></div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card h-100 border-success"><div class="card-body">
                <div class="text-secondary small">Marge</div>
                <div class="fs-5 fw-medium text-success">{{ montant($totalMarge) }}</div>
            </div></div>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Produit</th>
                        <th>Pièces vendues</th>
                        <th>Ventes</th>
                        <th>Coût</th>
                        <th>Marge</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($lignes as $ligne)
                        <tr>
                            <td>{{ $ligne->nom }} <code class="small">{{ $ligne->sku }}</code></td>
                            <td>{{ quantite($ligne->pieces) }}</td>
                            <td>{{ montant($ligne->ventes_total) }}</td>
                            <td>{{ montant($ligne->cout_total) }}</td>
                            <td class="{{ $ligne->marge >= 0 ? 'text-success' : 'text-danger' }} fw-medium">
                                {{ montant($ligne->marge) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-secondary py-4">Aucune vente sur cette période.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
