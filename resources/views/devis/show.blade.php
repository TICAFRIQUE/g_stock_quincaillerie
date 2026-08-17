@extends('layouts.app')

@section('title', "Devis {$devis->numero}")

@section('content')
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-2 mb-3 d-print-none">
        <div>
            <h2 class="h4 mb-1">Devis <code>{{ $devis->numero }}</code></h2>
            <span class="badge {{ $devis->statutEffectif()->classeBadge() }}">{{ $devis->statutEffectif()->libelle() }}</span>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <button type="button" class="btn btn-outline-secondary" onclick="imprimerDevis()">
                <i class="bi bi-printer me-1"></i>Imprimer
            </button>
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
            Stock insuffisant pour {{ $lignesEnRupture->count() > 1 ? 'ces produits' : 'ce produit' }} (toutes localisations confondues) —
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

    <div class="row g-3 mb-3 d-print-none">
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-secondary small">Client</div>
                    <div class="fw-medium"><a href="{{ route('clients.show', $devis->client) }}">{{ $devis->client->nom }}</a></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-secondary small">Créé le</div>
                    <div class="fw-medium">{{ $devis->created_at->format('d/m/Y') }} par {{ $devis->auteur->name }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-secondary small">Valide jusqu'au</div>
                    <div class="fw-medium">{{ $devis->date_validite->format('d/m/Y') }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card d-print-none">
        <div class="card-body">
            <p class="text-secondary small mb-0">Montants indicatifs — ceci est un devis, pas une facture.</p>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Désignation</th>
                        <th class="d-print-none">Stock</th>
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
                            <td class="d-print-none">
                                <div class="d-flex flex-column">
                                    @foreach ($stocksParProduit[$ligne->produit_id] ?? [] as $source)
                                        <span class="small">
                                            <span class="text-secondary">{{ $source['nom'] }}{{ $source['type'] === 'depot' ? ' (dépôt)' : '' }} : </span><span class="{{ $source['quantite'] === 0 ? 'text-danger' : 'text-secondary' }}">{{ $source['quantite'] }}</span>
                                        </span>
                                    @endforeach
                                </div>
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
                        <th colspan="5" class="text-end">Total indicatif</th>
                        <th class="text-end">{{ number_format($montants['total_net'], 0, ',', ' ') }} F</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- Contenu au format devis, masqué à l'écran : le bouton "Imprimer"
         imprime ce bloc en place (voir imprimerDevis() ci-dessous), sans
         redirection ni nouvel onglet — même geste que le ticket de vente. --}}
    <div id="factureImprimable" class="d-none">
        <table class="entete">
            <tr>
                <td style="width: 55%;">
                    @if ($logoDataUri)
                        <img src="{{ $logoDataUri }}" class="logo" alt="Logo">
                        <br>
                    @endif
                    <span class="entreprise-nom">{{ $parametre->nom }}</span><br>
                    @if ($parametre->adresse) {{ $parametre->adresse }}<br> @endif
                    @if ($parametre->numero) Tél : {{ $parametre->numero }} @endif
                </td>
                <td style="width: 45%;">
                    <div class="facture-titre">DEVIS</div>
                    <div class="facture-meta">
                        N° {{ $devis->numero }}<br>
                        Date : {{ $devis->created_at->format('d/m/Y') }}<br>
                        Valide jusqu'au : {{ $devis->date_validite->format('d/m/Y') }}
                    </div>
                </td>
            </tr>
        </table>

        <div class="bloc-client">
            <div class="label">Client</div>
            <strong>{{ $devis->client->nom }}</strong><br>
            @if ($devis->client->telephone) Tél : {{ $devis->client->telephone }}<br> @endif
            @if ($devis->client->adresse) {{ $devis->client->adresse }} @endif
        </div>

        <table class="lignes">
            <thead>
                <tr>
                    <th>Désignation</th>
                    <th>Unité</th>
                    <th class="text-end">Qté</th>
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
                    @endphp
                    <tr>
                        <td>{{ $ligne->produit->libelle_affichage }}</td>
                        <td>{{ $ligne->uniteVente->libelle ?? $ligne->produit->unite_base_libelle }}</td>
                        <td class="text-end">{{ $ligne->quantite }}</td>
                        <td class="text-end">{{ number_format($prixUnitaire, 0, ',', ' ') }} F</td>
                        <td class="text-end">{{ $remiseLigne > 0 ? '− '.number_format($remiseLigne, 0, ',', ' ').' F' : '—' }}</td>
                        <td class="text-end">{{ number_format($sousTotalLigne - $remiseLigne, 0, ',', ' ') }} F</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <table class="totaux">
            <tr class="net">
                <td>Total net</td>
                <td class="text-end">{{ number_format($montants['total_net'], 0, ',', ' ') }} F</td>
            </tr>
        </table>

        <div class="mention">
            Ce document est un devis, pas une facture — montants indicatifs, valables jusqu'au {{ $devis->date_validite->format('d/m/Y') }}.
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Bascule d'impression : affiche #factureImprimable le temps de
        // l'impression, puis revient à l'état par défaut (voir
        // resources/sass/app.scss, .impression-facture — même mécanisme que
        // le ticket de vente).
        function imprimerDevis() {
            document.body.classList.add('impression-facture');
            window.print();
        }
        window.addEventListener('afterprint', () => {
            document.body.classList.remove('impression-facture');
        });
    </script>
@endpush
