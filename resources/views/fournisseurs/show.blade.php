@extends('layouts.app')

@section('title', $fournisseur->nom)

@section('content')
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h2 class="h4 mb-1">{{ $fournisseur->nom }}</h2>
            <p class="text-secondary small mb-0">
                {{ $fournisseur->telephone ?? 'Aucun téléphone' }}
                @if ($fournisseur->email) — {{ $fournisseur->email }} @endif
                @if ($fournisseur->adresse) — {{ $fournisseur->adresse }} @endif
            </p>
        </div>
        <div class="d-flex gap-2">
            @can('fournisseur.reglement')
                @if ($solde > 0)
                    <a href="{{ route('reglements-fournisseur.create', $fournisseur) }}" class="btn btn-success">
                        <i class="bi bi-cash-coin me-1"></i>Régler
                    </a>
                @endif
            @endcan
            @can('fournisseur.gerer')
                <a href="{{ route('fournisseurs.edit', $fournisseur) }}" class="btn btn-outline-secondary">
                    <i class="bi bi-pencil me-1"></i>Modifier
                </a>
            @endcan
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-secondary small">Solde dû</div>
                    <div class="fw-medium fs-5 {{ $solde > 0 ? 'text-danger' : '' }}">{{ number_format($solde, 0, ',', ' ') }} F</div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-secondary small">Commandes d'achat</div>
                    <div class="fw-medium">{{ $fournisseur->commande_achats_count }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <h3 class="h6">Historique du compte</h3>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Référence</th>
                        <th>Auteur</th>
                        <th class="text-end">Montant</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($ecritures as $ecriture)
                        <tr class="{{ $ecriture->type->classeLigne() }}">
                            <td>{{ $ecriture->created_at->format('d/m/Y H:i') }}</td>
                            <td><span class="badge {{ $ecriture->type->classeBadge() }}">{{ $ecriture->type->libelle() }}</span></td>
                            <td>
                                @if ($ecriture->reference instanceof \App\Models\CommandeAchat)
                                    <a href="{{ route('commande-achats.show', $ecriture->reference) }}">{{ $ecriture->reference->numero }}</a>
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $ecriture->auteur?->name ?? 'Utilisateur supprimé' }}</td>
                            <td class="text-end fw-medium {{ $ecriture->montant > 0 ? 'text-danger' : 'text-success' }}">
                                {{ $ecriture->montant > 0 ? '+' : '' }}{{ number_format($ecriture->montant, 0, ',', ' ') }} F
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-secondary py-4">Aucun mouvement sur ce compte.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($ecritures->hasPages())
            <div class="card-body">
                {{ $ecritures->onEachSide(1)->links() }}
            </div>
        @endif
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <h3 class="h6 mb-0">Commandes d'achat</h3>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Numéro</th>
                        <th>Date</th>
                        <th>Destination(s)</th>
                        <th>Statut</th>
                        <th class="text-end">Total TTC</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($commandes as $commande)
                        <tr>
                            <td><code>{{ $commande->numero }}</code></td>
                            <td>{{ $commande->created_at->format('d/m/Y H:i') }}</td>
                            <td>{{ $commande->lignes->pluck('magasinDestination.nom')->unique()->implode(', ') }}</td>
                            <td>
                                @if ($commande->trashed())
                                    <span class="badge text-bg-danger">Annulée</span>
                                @elseif ($commande->statut === 'validee')
                                    <span class="badge text-bg-success">Validée</span>
                                @else
                                    <span class="badge text-bg-secondary">Brouillon</span>
                                @endif
                            </td>
                            <td class="text-end">{{ number_format($commande->totalTtc(), 0, ',', ' ') }} F</td>
                            <td class="text-end">
                                <a href="{{ route('commande-achats.show', $commande) }}" class="btn btn-sm btn-icon btn-outline-secondary" title="Détail de la commande">
                                    <i class="bi bi-eye"></i>
                                    <span class="visually-hidden">Détail</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-secondary py-4">Aucune commande pour ce fournisseur.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($commandes->hasPages())
            <div class="card-body">
                {{ $commandes->onEachSide(1)->links() }}
            </div>
        @endif
    </div>

    <div class="mt-3">
        <a href="{{ route('fournisseurs.index') }}" class="btn btn-link ps-0">Retour à la liste</a>
    </div>
@endsection
