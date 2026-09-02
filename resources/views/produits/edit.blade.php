@extends('layouts.app')

@section('title', 'Modifier le produit')

@section('content')
    <h2 class="h4 mb-3">Modifier « {{ $produit->libelle_affichage }} »</h2>

    <div class="row g-4">
        <div class="col-12 col-lg-7">
            <div class="card">
                <div class="card-body">
                    @if ($produit->getFirstMediaUrl('image'))
                        <img src="{{ $produit->getFirstMediaUrl('image') }}" alt="" class="rounded mb-3" style="max-height: 160px;">
                    @else
                        <div class="bg-light rounded mb-3 d-flex align-items-center justify-content-center text-secondary" style="width:160px;height:160px;font-size:2.5rem;">
                            <i class="bi bi-box-seam"></i>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('produits.update', $produit) }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        @include('produits._form')

                        @if ($peutModifier)
                            <button type="submit" class="btn btn-primary">Enregistrer</button>
                        @endif
                        <a href="{{ route('produits.index') }}" class="btn btn-link">Retour à la liste</a>
                    </form>

                    @if ($peutSupprimer)
                        <div class="mt-3">
                            <x-delete-button :action="route('produits.destroy', $produit)" :label="'le produit « '.$produit->libelle_affichage.' »'" />
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-5">
            <div class="card">
                <div class="card-body">
                    <h3 class="h6">Unités de vente</h3>
                    <p class="text-secondary small">
                        L'unité de base « {{ $produit->unite_base_libelle }} » (prix : {{ montant($produit->prix_piece) }}) est toujours vendable.
                        Ajoutez ici des variantes (ex. « 5L », « Carton de 24 ») avec leur propre libellé et prix total.
                    </p>

                    @if ($produit->uniteVentes->isNotEmpty())
                        <div x-data="{ editingId: null }">
                            <table class="table table-sm align-middle">
                                <thead>
                                    <tr>
                                        <th>Libellé</th>
                                        <th>Facteur</th>
                                        <th>Prix</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($produit->uniteVentes as $uniteVente)
                                        <tr x-show="editingId !== {{ $uniteVente->id }}">
                                            <td>{{ $uniteVente->libelle }}</td>
                                            <td>{{ $uniteVente->facteur }} × {{ $produit->unite_base_libelle }}</td>
                                            <td>{{ montant($uniteVente->prix) }}</td>
                                            <td class="text-end">
                                                @if ($peutModifier)
                                                    <button type="button" class="btn btn-sm btn-icon btn-outline-secondary" title="Modifier"
                                                            @click="editingId = {{ $uniteVente->id }}">
                                                        <i class="bi bi-pencil"></i>
                                                        <span class="visually-hidden">Modifier</span>
                                                    </button>
                                                    <x-delete-button :action="route('produits.unite-ventes.destroy', [$produit, $uniteVente])" :label="'l\'unité de vente « '.$uniteVente->libelle.' »'" />
                                                @endif
                                            </td>
                                        </tr>
                                        @if ($peutModifier)
                                            <tr x-show="editingId === {{ $uniteVente->id }}" x-cloak>
                                                <td colspan="4">
                                                    <form method="POST" action="{{ route('produits.unite-ventes.update', [$produit, $uniteVente]) }}" class="row g-2 align-items-end">
                                                        @csrf
                                                        @method('PUT')
                                                        <div class="col-4">
                                                            <label class="form-label small">Unité<span class="required-marker">*</span></label>
                                                            <select name="unite_id" class="form-select form-select-sm" required>
                                                                @foreach ($unites as $uniteOption)
                                                                    <option value="{{ $uniteOption->id }}" @selected($uniteVente->unite_id === $uniteOption->id)>{{ $uniteOption->nom }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div class="col-3">
                                                            <label class="form-label small">Facteur<span class="required-marker">*</span></label>
                                                            <input type="number" name="facteur" class="form-control form-control-sm" value="{{ $uniteVente->facteur }}" min="2" required>
                                                        </div>
                                                        <div class="col-3">
                                                            <label class="form-label small">Prix ({{ App\Models\Devise::abreviationActuelle() }})<span class="required-marker">*</span></label>
                                                            <input type="number" name="prix" class="form-control form-control-sm" value="{{ $uniteVente->prix }}" min="0" required>
                                                        </div>
                                                        <div class="col-2 d-flex gap-1">
                                                            <button type="submit" class="btn btn-sm btn-icon btn-primary" title="Enregistrer">
                                                                <i class="bi bi-check-lg"></i>
                                                                <span class="visually-hidden">Enregistrer</span>
                                                            </button>
                                                        </div>
                                                        <div class="col-12">
                                                            <button type="button" class="btn btn-sm btn-link p-0" @click="editingId = null">Annuler</button>
                                                        </div>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-secondary fst-italic">Aucune unité de vente pour l'instant.</p>
                    @endif

                    @if ($peutModifier)
                        <hr>
                        <form method="POST" action="{{ route('produits.unite-ventes.store', $produit) }}" class="row g-2 align-items-end">
                            @csrf
                            <div class="col-4">
                                <label class="form-label small">Unité<span class="required-marker">*</span></label>
                                <select name="unite_id" class="form-select form-select-sm" required>
                                    <option value="">— Choisir —</option>
                                    @foreach ($unites as $uniteOption)
                                        <option value="{{ $uniteOption->id }}">{{ $uniteOption->nom }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-3">
                                <label class="form-label small">Facteur<span class="required-marker">*</span></label>
                                <input type="number" name="facteur" class="form-control form-control-sm" min="2" placeholder="Ex. 5" required>
                            </div>
                            <div class="col-3">
                                <label class="form-label small">Prix ({{ App\Models\Devise::abreviationActuelle() }})<span class="required-marker">*</span></label>
                                <input type="number" name="prix" class="form-control form-control-sm" min="0" required>
                            </div>
                            <div class="col-2">
                                <button type="submit" class="btn btn-sm btn-icon btn-primary" title="Ajouter">
                                    <i class="bi bi-plus-lg"></i>
                                    <span class="visually-hidden">Ajouter</span>
                                </button>
                            </div>
                        </form>
                        <div class="form-text">Facteur = nombre d'unités de base contenues (ex. 5 pour un bidon de 5L si l'unité de base est le litre).</div>
                        @error('unite_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        @error('facteur') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        @error('prix') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
