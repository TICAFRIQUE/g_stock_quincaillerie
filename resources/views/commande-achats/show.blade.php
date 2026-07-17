@extends('layouts.app')

@section('title', "Commande {$commande->numero}")

@section('content')
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h2 class="h4 mb-1">Commande <code>{{ $commande->numero }}</code></h2>
            @if ($commande->statut === 'validee')
                <span class="badge text-bg-success">Validée le {{ $commande->valide_at->format('d/m/Y à H:i') }} par {{ $commande->validateur->name }}</span>
            @else
                <span class="badge text-bg-secondary">Brouillon</span>
            @endif
        </div>

        <div class="d-flex gap-2">
            @if ($commande->statut === 'brouillon')
                @if ($peutValider)
                    <x-confirm-button :action="route('commande-achats.valider', $commande)"
                        message="Valider cet achat maintenant ? Le stock sera mis à jour immédiatement et cette action est irréversible."
                        button-label="Valider l'achat" button-class="btn-success" icon="bi-check-circle" />
                @endif
                @if ($peutSupprimer)
                    <x-delete-button :action="route('commande-achats.destroy', $commande)" :label="'la commande « '.$commande->numero.' »'" />
                @endif
            @endif
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-secondary small">Fournisseur</div>
                    <div class="fw-medium">{{ $commande->fournisseur->nom }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-secondary small">Magasin (destination)</div>
                    <div class="fw-medium">{{ $commande->magasin->nom }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-secondary small">Date</div>
                    <div class="fw-medium">{{ $commande->date_commande->format('d/m/Y') }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>SKU</th>
                        <th>Produit</th>
                        <th>Quantité</th>
                        <th>Prix d'achat</th>
                        <th>Sous-total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($commande->lignes as $ligne)
                        <tr>
                            <td><code>{{ $ligne->produit->sku }}</code></td>
                            <td>{{ $ligne->produit->libelle_affichage }}</td>
                            <td>{{ $ligne->quantite }} pièces</td>
                            <td>{{ number_format($ligne->prix_achat, 0, ',', ' ') }} F</td>
                            <td>{{ number_format($ligne->quantite * $ligne->prix_achat, 0, ',', ' ') }} F</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="4" class="text-end">Total</th>
                        <th>{{ number_format($commande->lignes->sum(fn ($l) => $l->quantite * $l->prix_achat), 0, ',', ' ') }} F</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="mt-3">
        <a href="{{ route('commande-achats.index') }}" class="btn btn-link ps-0">Retour à la liste</a>
    </div>
@endsection
