@extends('layouts.app')

@section('title', 'Stock')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h4 mb-0">Stock</h2>
        <div class="d-flex gap-2">
            <a href="{{ route('stock.mouvements.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-clock-history me-1"></i>Historique des mouvements
            </a>
            @can('stock.transferer')
                <a href="{{ route('transferts.create') }}" class="btn btn-outline-primary">
                    <i class="bi bi-arrow-left-right me-1"></i>Transfert
                </a>
            @endcan
            @can('stock.ajuster')
                <a href="{{ route('stock.mouvements.create') }}" class="btn btn-outline-primary">
                    <i class="bi bi-sliders me-1"></i>Casse / ajustement
                </a>
            @endcan
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-secondary small">Pièces en stock</div>
                    <div class="fs-5 fw-medium">{{ number_format($kpis['totalPieces'], 0, ',', ' ') }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-secondary small">Valeur du stock (CMP)</div>
                    <div class="fs-5 fw-medium">{{ number_format($kpis['valeurStock'], 0, ',', ' ') }} F</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-secondary small">Produits sous seuil</div>
                    <div class="fs-5 fw-medium {{ $kpis['sousSeuil'] > 0 ? 'text-danger' : '' }}">{{ $kpis['sousSeuil'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-secondary small">Magasin(s) concerné(s)</div>
                    <div class="fs-5 fw-medium">{{ $kpis['nbMagasins'] }}</div>
                </div>
            </div>
        </div>
    </div>

    <form method="GET" action="{{ route('stock.index') }}" class="row g-2 mb-3">
        <div class="col-auto">
            <select name="magasin_id" class="form-select" onchange="this.form.submit()">
                <option value="">Tous les magasins</option>
                @foreach ($magasins as $magasin)
                    <option value="{{ $magasin->id }}" @selected(request('magasin_id') == $magasin->id)>{{ $magasin->nom }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-auto d-flex align-items-center">
            <div class="form-check">
                <input type="checkbox" name="sous_seuil" value="1" id="sous_seuil" class="form-check-input" onchange="this.form.submit()" @checked(request()->boolean('sous_seuil'))>
                <label for="sous_seuil" class="form-check-label">Sous le seuil d'alerte uniquement</label>
            </div>
        </div>
        @if (request()->hasAny(['magasin_id', 'sous_seuil', 'tri', 'direction']))
            <div class="col-auto">
                <a href="{{ route('stock.index') }}" class="btn btn-outline-danger">
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
                        <x-th-tri champ="nom" label="Produit" />
                        <th>Magasin</th>
                        <x-th-tri champ="quantite" label="Quantité" />
                        <th>Seuil d'alerte</th>
                        <th>Coût moyen pondéré</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($stocks as $stock)
                        @php $sousSeuil = $stock->quantite <= $stock->produit->seuil_alerte; @endphp
                        <tr class="{{ $sousSeuil ? 'table-danger' : '' }}">
                            <td>{{ $stock->produit->libelle_affichage }} <code class="small">{{ $stock->produit->sku }}</code></td>
                            <td>{{ $stock->magasin->nom }}</td>
                            <td>
                                {{ $stock->quantite }} pièces
                                @if ($sousSeuil)
                                    <i class="bi bi-exclamation-triangle-fill text-danger ms-1" title="Sous le seuil d'alerte"></i>
                                @endif
                            </td>
                            <td>{{ $stock->produit->seuil_alerte }}</td>
                            <td>{{ number_format($stock->cout_moyen_pondere, 0, ',', ' ') }} F</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-secondary py-4">Aucun stock enregistré pour l'instant.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $stocks->links() }}
    </div>
@endsection
