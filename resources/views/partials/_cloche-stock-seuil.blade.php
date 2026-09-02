@php
    $requeteSousSeuil = \App\Models\Stock::query()
        ->join('produits', 'produits.id', '=', 'stocks.produit_id')
        ->whereColumn('stocks.quantite', '<=', 'produits.seuil_alerte')
        ->when(auth()->user()->magasin_id, fn ($q, $magasinId) => $q->where('stocks.magasin_id', $magasinId));

    $nbSousSeuil = (clone $requeteSousSeuil)->count();

    $stocksSousSeuil = (clone $requeteSousSeuil)
        ->select('stocks.*')
        ->orderBy('stocks.quantite')
        ->with(['produit', 'magasin'])
        ->limit(10)
        ->get();
@endphp
<div class="dropdown">
    <button class="btn btn-light position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Stock sous seuil">
        <i class="bi bi-box-seam"></i>
        @if ($nbSousSeuil > 0)
            <span class="badge rounded-pill text-bg-danger position-absolute top-0 start-100 translate-middle">
                {{ $nbSousSeuil }}
                <span class="visually-hidden">produits sous seuil</span>
            </span>
        @endif
    </button>
    <div class="dropdown-menu dropdown-menu-end p-0" style="width: 340px; max-height: 400px; overflow-y: auto;">
        <div class="px-3 py-2 border-bottom">
            <h6 class="mb-0">Stock sous seuil</h6>
        </div>
        @forelse ($stocksSousSeuil as $stock)
            <a href="{{ route('stock.index', ['magasin_id' => $stock->magasin_id, 'sous_seuil' => 1]) }}"
               class="dropdown-item px-3 py-2 border-bottom" style="white-space: normal;">
                <div class="small fw-medium text-warning-emphasis">
                    <i class="bi bi-box-seam me-1"></i>{{ $stock->produit->libelle_affichage }}
                </div>
                <div class="small text-secondary">
                    {{ $stock->magasin->nom }} — {{ quantite($stock->quantite) }} / seuil {{ $stock->produit->seuil_alerte }}
                </div>
                <div class="small text-secondary"><code>{{ $stock->produit->sku }}</code></div>
            </a>
        @empty
            <div class="p-3 text-center text-secondary small">Aucun produit sous seuil.</div>
        @endforelse
        @if ($nbSousSeuil > $stocksSousSeuil->count())
            <a href="{{ route('stock.index', ['sous_seuil' => 1]) }}" class="dropdown-item px-3 py-2 text-center small">
                Voir les {{ $nbSousSeuil }} produits sous seuil
            </a>
        @endif
    </div>
</div>
