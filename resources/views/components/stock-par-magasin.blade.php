@props(['produit', 'magasins'])

<div class="stock-par-magasin">
    @forelse ($produit->stockParMagasin($magasins) as $ligne)
        <div class="small {{ $ligne['sous_seuil'] ? 'text-danger' : '' }}">
            {{ $ligne['magasin']->nom }} :
            {{ quantite($ligne['quantite']) }} {{ $produit->unite_base_libelle_complet }}
            @if ($ligne['sous_seuil'])
                <i class="bi bi-exclamation-triangle-fill ms-1" title="Sous le seuil d'alerte"></i>
            @endif
        </div>
    @empty
        <span class="text-secondary small">—</span>
    @endforelse
</div>
