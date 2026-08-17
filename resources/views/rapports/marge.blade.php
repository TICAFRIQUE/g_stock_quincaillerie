@extends('layouts.app')

@section('title', 'Rapport de marge')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-1">
        <h2 class="h4 mb-0">Rapport de marge</h2>
        <div class="d-flex gap-2 flex-wrap">
            <x-bouton-imprimer />
            <a href="{{ route('rapports.marge.pdf', request()->query()) }}" class="btn btn-outline-secondary d-print-none">
                <i class="bi bi-file-earmark-pdf me-1"></i>PDF
            </a>
            <a href="{{ route('rapports.marge.excel', request()->query()) }}" class="btn btn-outline-secondary d-print-none">
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
                <a href="{{ route('rapports.marge') }}" class="btn btn-sm btn-outline-danger" title="Réinitialiser les filtres">
                    <i class="bi bi-x-circle me-1"></i>Réinitialiser
                </a>
            </div>
        @endif
    </form>

    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
            <div class="card h-100"><div class="card-body">
                <div class="text-secondary small">Ventes</div>
                <div class="fs-5 fw-medium">{{ number_format($totalVentes, 0, ',', ' ') }} F</div>
            </div></div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card h-100"><div class="card-body">
                <div class="text-secondary small">Coût</div>
                <div class="fs-5 fw-medium">{{ number_format($totalCout, 0, ',', ' ') }} F</div>
            </div></div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card h-100 border-success"><div class="card-body">
                <div class="text-secondary small">Marge</div>
                <div class="fs-5 fw-medium text-success">{{ number_format($totalMarge, 0, ',', ' ') }} F</div>
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
                            <td>{{ $ligne->pieces }}</td>
                            <td>{{ number_format($ligne->ventes_total, 0, ',', ' ') }} F</td>
                            <td>{{ number_format($ligne->cout_total, 0, ',', ' ') }} F</td>
                            <td class="{{ $ligne->marge >= 0 ? 'text-success' : 'text-danger' }} fw-medium">
                                {{ number_format($ligne->marge, 0, ',', ' ') }} F
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
