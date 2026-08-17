@extends('layouts.app')

@section('title', 'Produits')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3 d-print-none">
        <h2 class="h4 mb-0">Produits</h2>
        <div class="d-flex gap-2 flex-wrap">
            <x-export-buttons :pdf-route="route('produits.pdf', request()->query())" :excel-route="route('produits.excel', request()->query())" :tout="true" />
            @can('produit.creer')
                <a href="{{ route('produits.create') }}" class="btn btn-primary">Nouveau produit</a>
            @endcan
        </div>
    </div>

    <h2 class="h4 mb-3 d-none d-print-block">Produits</h2>

    <div class="d-print-none">
        <x-recherche-form :action="route('produits.index')" placeholder="SKU, nom ou code-barres…" />
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th></th>
                        <x-th-tri champ="sku" label="SKU" />
                        <x-th-tri champ="nom" label="Nom" />
                        <th>Catégorie</th>
                        <x-th-tri champ="prix_piece" label="Prix / unité" />
                        <th>Seuil d'alerte</th>
                        <x-th-tri champ="actif" label="Statut" />
                        <th class="text-end d-print-none">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($produits as $produit)
                        <tr>
                            <td style="width: 48px;">
                                @if ($produit->getFirstMediaUrl('image'))
                                    <img src="{{ $produit->getFirstMediaUrl('image', 'thumb') ?: $produit->getFirstMediaUrl('image') }}" alt="" width="40" height="40" class="rounded object-fit-cover">
                                @else
                                    <div class="bg-light rounded d-flex align-items-center justify-content-center text-secondary" style="width:40px;height:40px;">
                                        <i class="bi bi-tools"></i>
                                    </div>
                                @endif
                            </td>
                            <td><code>{{ $produit->sku }}</code></td>
                            <td>{{ $produit->libelle_affichage }}</td>
                            <td>{{ $produit->categorie->nom }}</td>
                            <td>{{ number_format($produit->prix_piece, 0, ',', ' ') }} F / {{ $produit->unite_base_libelle }}</td>
                            <td>{{ $produit->seuil_alerte }}</td>
                            <td>
                                @if ($produit->actif)
                                    <span class="badge text-bg-success">Actif</span>
                                @else
                                    <span class="badge text-bg-secondary">Inactif</span>
                                @endif
                            </td>
                            <td class="text-end d-print-none">
                                <x-edit-button :href="route('produits.edit', $produit)" />
                                @can('produit.supprimer')
                                    <x-delete-button :action="route('produits.destroy', $produit)" :label="'le produit « '.$produit->libelle_affichage.' »'" />
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-secondary py-4">Aucun produit trouvé.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($produits instanceof \Illuminate\Pagination\LengthAwarePaginator)
        <div class="mt-3 d-print-none">
            {{ $produits->links() }}
        </div>
    @endif
@endsection
