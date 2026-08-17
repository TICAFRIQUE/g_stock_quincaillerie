@extends('layouts.app')

@section('title', 'Rapport des ventes')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-1">
        <h2 class="h4 mb-0">Rapport des ventes</h2>
        <div class="d-flex gap-2 flex-wrap">
            <x-bouton-imprimer tout />
            <a href="{{ route('rapports.ventes.pdf', request()->query()) }}" class="btn btn-outline-secondary d-print-none">
                <i class="bi bi-file-earmark-pdf me-1"></i>PDF
            </a>
            <a href="{{ route('rapports.ventes.excel', request()->query()) }}" class="btn btn-outline-secondary d-print-none">
                <i class="bi bi-file-earmark-excel me-1"></i>Excel
            </a>
        </div>
    </div>
    @php
        $magasinIdActif = auth()->user()->magasin_id ?: request('magasin_id');
        $filtresActifs = collect([
            $magasinIdActif ? 'Magasin : ' . $magasins->firstWhere('id', (int) $magasinIdActif)?->nom : null,
            request('caissier_id') ? 'Caissier : ' . $caissiers->firstWhere('id', (int) request('caissier_id'))?->name : null,
            request('caisse_id') ? 'Caisse : ' . $caisses->firstWhere('id', (int) request('caisse_id'))?->nom : null,
        ])->filter();
    @endphp
    <p class="text-secondary small mb-3">
        Période : du {{ $debut->format('d/m/Y') }} au {{ $fin->format('d/m/Y') }}
        @if ($filtresActifs->isNotEmpty())
            · {{ $filtresActifs->implode(' · ') }}
        @endif
    </p>

    <form method="GET" action="{{ route('rapports.ventes') }}" class="row g-2 mb-3 d-print-none">
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
            <select name="caissier_id" class="form-select form-select-sm">
                <option value="">Tous les caissiers</option>
                @foreach ($caissiers as $caissier)
                    <option value="{{ $caissier->id }}" @selected(request('caissier_id') == $caissier->id)>{{ $caissier->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-auto">
            <select name="caisse_id" class="form-select form-select-sm">
                <option value="">Toutes les caisses</option>
                @foreach ($caisses as $caisse)
                    <option value="{{ $caisse->id }}" @selected(request('caisse_id') == $caisse->id)>{{ $caisse->nom }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-sm btn-primary">Filtrer</button>
        </div>
        @if (request()->hasAny(['debut', 'fin', 'magasin_id', 'caissier_id', 'caisse_id']))
            <div class="col-auto">
                <a href="{{ route('rapports.ventes') }}" class="btn btn-sm btn-outline-danger" title="Réinitialiser les filtres">
                    <i class="bi bi-x-circle me-1"></i>Réinitialiser
                </a>
            </div>
        @endif
    </form>

    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
            <div class="card h-100"><div class="card-body">
                <div class="text-secondary small">Nombre de ventes</div>
                <div class="fs-5 fw-medium">{{ $nombre }}</div>
            </div></div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card h-100"><div class="card-body">
                <div class="text-secondary small">Total net</div>
                <div class="fs-5 fw-medium">{{ number_format($totalNet, 0, ',', ' ') }} F</div>
            </div></div>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Numéro</th>
                        <th>Date</th>
                        <th>Magasin</th>
                        <th>Caisse</th>
                        <th>Caissier</th>
                        <th>Total net</th>
                        <th class="text-end d-print-none">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($ventes as $vente)
                        <tr>
                            <td><code>{{ $vente->numero }}</code></td>
                            <td>{{ $vente->created_at->format('d/m/Y H:i') }}</td>
                            <td>{{ $vente->magasin->nom }}</td>
                            <td>{{ $vente->sessionCaisse->caisse->nom }}</td>
                            <td>{{ $vente->caissier->name }}</td>
                            <td>{{ number_format($vente->total_net, 0, ',', ' ') }} F</td>
                            <td class="text-end d-print-none">
                                <a href="{{ route('ventes.ticket', $vente) }}" class="btn btn-sm btn-icon btn-outline-secondary" title="Détail de la vente">
                                    <i class="bi bi-eye"></i>
                                    <span class="visually-hidden">Détail</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-secondary py-4">Aucune vente sur cette période.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($ventes instanceof \Illuminate\Pagination\LengthAwarePaginator)
        <div class="mt-3 d-print-none">
            {{ $ventes->links() }}
        </div>
    @endif
@endsection
