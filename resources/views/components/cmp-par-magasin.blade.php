@props(['produit', 'magasins'])

<div class="cmp-par-magasin">
    @forelse ($produit->stockParMagasin($magasins) as $ligne)
        <div class="small">{{ $ligne['magasin']->nom }} : {{ montant($ligne['cout_moyen_pondere']) }}</div>
    @empty
        <span class="text-secondary small">—</span>
    @endforelse
</div>
