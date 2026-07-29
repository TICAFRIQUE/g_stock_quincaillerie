@extends('layouts.app')

@section('title', "Commande {$commande->numero}")

@section('content')
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h2 class="h4 mb-1">Commande <code>{{ $commande->numero }}</code></h2>
            @if ($commande->trashed())
                <span class="badge text-bg-danger">Annulée</span>
            @elseif ($commande->statut === 'validee')
                <span class="badge text-bg-success">Validée le {{ $commande->valide_at->format('d/m/Y à H:i') }} par {{ $commande->validateur->name }}</span>
            @else
                <span class="badge text-bg-secondary">Brouillon</span>
            @endif
        </div>

        <div class="d-flex gap-2">
            @if (! $commande->trashed() && $commande->statut === 'brouillon')
                @if ($peutValider)
                    <x-confirm-button :action="route('commande-achats.valider', $commande)"
                        message="Valider cet achat maintenant ? Le stock sera mis à jour immédiatement et cette action est irréversible."
                        button-label="Valider l'achat" button-class="btn-success" icon="bi-check-circle" />
                @endif
                @if ($peutAnnuler)
                    <x-delete-button :action="route('commande-achats.destroy', $commande)" :label="'la commande « '.$commande->numero.' »'" />
                @endif
            @endif
        </div>
    </div>

    @if ($commande->trashed())
        <div class="alert alert-danger mb-3">
            <i class="bi bi-x-circle-fill me-1"></i>
            <strong>Commande annulée</strong> par {{ $commande->annulateur?->name ?? 'un utilisateur supprimé' }}
            le {{ $commande->deleted_at->format('d/m/Y H:i') }}.
            <div class="mt-1 fst-italic">{{ $commande->motif_annulation }}</div>
        </div>
    @elseif ($commande->statut === 'validee' && $peutAnnuler)
        <div class="card mb-3 border-danger" style="max-width: 480px;" x-data="{ ouvert: false }">
            <div class="card-body">
                <button type="button" class="btn btn-outline-danger btn-sm w-100" @click="ouvert = !ouvert" x-show="!ouvert">
                    <i class="bi bi-x-circle me-1"></i>Annuler cet achat
                </button>

                <form id="formAnnulerAchat" method="POST" action="{{ route('commande-achats.annuler', $commande) }}" x-show="ouvert" x-cloak>
                    @csrf
                    <label for="motif-annulation" class="form-label small mt-2">
                        Motif de l'annulation (obligatoire) — le stock sera remis à jour automatiquement
                    </label>
                    <textarea name="motif" id="motif-annulation" class="form-control form-control-sm mb-2" rows="2" required></textarea>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-danger btn-sm flex-fill"
                                data-bs-toggle="modal" data-bs-target="#confirmActionModal"
                                data-form-id="formAnnulerAchat"
                                data-message="Annuler cet achat ? Le stock sera décrémenté en conséquence (échoue si une partie a déjà été vendue ou transférée ailleurs). Cette action est irréversible."
                                data-button-label="Annuler l'achat" data-button-class="btn-danger">
                            Annuler l'achat
                        </button>
                        <button type="button" class="btn btn-link btn-sm" @click="ouvert = false">Fermer</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

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
                        <th>Quantité achetée</th>
                        <th>Qté en pièces (stock)</th>
                        <th>Prix d'achat</th>
                        <th>Sous-total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($commande->lignes as $ligne)
                        <tr>
                            <td><code>{{ $ligne->produit->sku }}</code></td>
                            <td>{{ $ligne->produit->libelle_affichage }}</td>
                            <td>
                                @if ($ligne->unite_achat === 'groupe')
                                    {{ $ligne->quantite }} groupe{{ $ligne->quantite > 1 ? 's' : '' }} de {{ $ligne->qte_par_groupe }}
                                @else
                                    {{ $ligne->quantite }} pièce{{ $ligne->quantite > 1 ? 's' : '' }}
                                @endif
                            </td>
                            <td>{{ $ligne->quantite_pieces }} pièces</td>
                            <td>
                                {{ number_format($ligne->prix_achat, 0, ',', ' ') }} F
                                @if ($ligne->unite_achat === 'groupe')
                                    <div class="text-secondary small">soit {{ number_format($ligne->prixAchatParPiece(), 0, ',', ' ') }} F / pièce</div>
                                @endif
                            </td>
                            <td>{{ number_format($ligne->quantite * $ligne->prix_achat, 0, ',', ' ') }} F</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="5" class="text-end">Total</th>
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
