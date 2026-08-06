@extends('layouts.app')

@section('title', "Devis {$devis->numero}")

@section('content')
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-2 mb-3 d-print-none">
        <div>
            <h2 class="h4 mb-1">Devis <code>{{ $devis->numero }}</code></h2>
            <span class="badge {{ $devis->statutEffectif()->classeBadge() }}">{{ $devis->statutEffectif()->libelle() }}</span>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('devis.facture', $devis) }}" class="btn btn-outline-secondary" target="_blank" rel="noopener">
                <i class="bi bi-printer me-1"></i>Voir / Imprimer
            </a>
            <a href="{{ route('devis.pdf', $devis) }}" class="btn btn-outline-secondary">
                <i class="bi bi-file-earmark-pdf me-1"></i>PDF
            </a>
            <a href="{{ route('devis.excel', $devis) }}" class="btn btn-outline-secondary">
                <i class="bi bi-file-earmark-excel me-1"></i>Excel
            </a>

            @can('devis.gerer')
                @if ($devis->peutEtreModifie())
                    <a href="{{ route('devis.edit', $devis) }}" class="btn btn-outline-secondary">
                        <i class="bi bi-pencil me-1"></i>Modifier
                    </a>
                @endif
                @if ($devis->peutEtreAnnule())
                    <x-confirm-button :action="route('devis.annuler', $devis)"
                        message="Refuser / annuler ce devis ? Il ne pourra plus être transformé en vente."
                        button-label="Refuser / annuler" button-class="btn-outline-danger" icon="bi-x-circle" />
                @endif
            @endcan

            @can('devis.transformer')
                @if ($devis->peutEtreTransforme())
                    @if ($sessionOuverte)
                        <a href="{{ route('devis.transformer.form', [$sessionOuverte, $devis]) }}" class="btn btn-success">
                            <i class="bi bi-arrow-repeat me-1"></i>Transformer en vente
                        </a>
                    @else
                        <a href="{{ route('sessions.index') }}" class="btn btn-outline-success" title="Ouvrez une session de caisse pour transformer ce devis">
                            <i class="bi bi-arrow-repeat me-1"></i>Ouvrir une caisse pour transformer
                        </a>
                    @endif
                @endif
            @endcan

            <a href="{{ route('devis.index') }}" class="btn btn-link">
                <i class="bi bi-arrow-left me-1"></i>Retour à la liste
            </a>
        </div>
    </div>

    @if ($devis->statutEffectif() === \App\Enums\DevisStatut::Expire)
        <div class="alert alert-dark d-print-none">
            <i class="bi bi-clock-history me-1"></i>
            Ce devis a expiré le {{ $devis->date_validite->format('d/m/Y') }} et ne peut plus être transformé. Dupliquez-en un nouveau si besoin.
        </div>
    @endif

    @if ($devis->statut === \App\Enums\DevisStatut::Transforme && $devis->vente)
        <div class="alert alert-success d-print-none">
            <i class="bi bi-check-circle-fill me-1"></i>
            Transformé en vente le {{ $devis->transforme_at->format('d/m/Y à H:i') }} —
            <a href="{{ route('ventes.ticket', $devis->vente) }}">voir le ticket {{ $devis->vente->numero }}</a>.
        </div>
    @endif

    @if ($lignesEnRupture->isNotEmpty())
        <div class="alert alert-warning d-print-none">
            <i class="bi bi-exclamation-triangle-fill me-1"></i>
            Stock insuffisant pour {{ $lignesEnRupture->count() > 1 ? 'ces produits' : 'ce produit' }} au magasin {{ $devis->magasin->nom }} —
            le devis ne peut pas être transformé en vente en l'état.
            <ul class="mb-0 mt-1">
                @foreach ($lignesEnRupture as $etat)
                    <li>
                        {{ $etat['ligne']->produit->libelle_affichage }} :
                        {{ $etat['demande'] }} pièce(s) demandée(s), {{ $etat['disponible'] }} disponible(s)
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-secondary small">Client</div>
                    <div class="fw-medium"><a href="{{ route('clients.show', $devis->client) }}">{{ $devis->client->nom }}</a></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-secondary small">Magasin</div>
                    <div class="fw-medium">{{ $devis->magasin->nom }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-secondary small">Créé le</div>
                    <div class="fw-medium">{{ $devis->created_at->format('d/m/Y') }} par {{ $devis->auteur->name }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-secondary small">Valide jusqu'au</div>
                    <div class="fw-medium">{{ $devis->date_validite->format('d/m/Y') }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <p class="text-secondary small mb-0">Montants indicatifs — ceci est un devis, pas une facture.</p>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Désignation</th>
                        <th>Qté</th>
                        <th class="text-end">Prix unitaire</th>
                        <th class="text-end">Remise</th>
                        <th class="text-end">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($devis->lignes as $ligne)
                        @php
                            $prixUnitaire = $ligne->uniteVente->prix ?? $ligne->produit->prix_piece;
                            $sousTotalLigne = $prixUnitaire * $ligne->quantite;
                            $remiseLigne = \App\Support\Remise::resoudre($ligne->remise_type, $ligne->remise_valeur, $sousTotalLigne);
                            $enRupture = $lignesEnRupture->firstWhere(fn ($etat) => $etat['ligne']->id === $ligne->id);
                        @endphp
                        <tr>
                            <td>
                                {{ $ligne->produit->libelle_affichage }}
                                <span class="text-secondary small">({{ $ligne->uniteVente?->libelle ?? $ligne->produit->unite_base_libelle }})</span>
                                @if ($enRupture)
                                    <span class="badge bg-warning text-dark ms-1" title="Stock insuffisant : {{ $enRupture['disponible'] }} disponible(s) sur {{ $enRupture['demande'] }} demandé(s)">
                                        <i class="bi bi-exclamation-triangle"></i> Stock insuffisant
                                    </span>
                                @endif
                            </td>
                            <td>{{ $ligne->quantite }}</td>
                            <td class="text-end">{{ number_format($prixUnitaire, 0, ',', ' ') }} F</td>
                            <td class="text-end text-danger">{{ $remiseLigne > 0 ? '− '.number_format($remiseLigne, 0, ',', ' ').' F' : '—' }}</td>
                            <td class="text-end fw-medium">{{ number_format($sousTotalLigne - $remiseLigne, 0, ',', ' ') }} F</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="fw-bold fs-5">
                        <th colspan="4" class="text-end">Total indicatif</th>
                        <th class="text-end">{{ number_format($montants['total_net'], 0, ',', ' ') }} F</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
@endsection
