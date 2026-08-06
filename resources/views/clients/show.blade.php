@extends('layouts.app')

@section('title', $client->nom)

@section('content')
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h2 class="h4 mb-1">{{ $client->nom }}</h2>
            <p class="text-secondary small mb-0">
                {{ $client->telephone ?? 'Aucun téléphone' }}
                @if ($client->adresse) — {{ $client->adresse }} @endif
            </p>
        </div>
        @can('client.gerer')
            <a href="{{ route('clients.edit', $client) }}" class="btn btn-outline-secondary">
                <i class="bi bi-pencil me-1"></i>Modifier
            </a>
        @endcan
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-secondary small">Solde dû</div>
                    <div class="fw-medium fs-5 {{ $solde > 0 ? 'text-danger' : '' }}">{{ number_format($solde, 0, ',', ' ') }} F</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-secondary small">Limite de crédit</div>
                    <div class="fw-medium">{{ $client->limite_credit !== null ? number_format($client->limite_credit, 0, ',', ' ').' F' : 'Illimitée' }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-secondary small">Ventes</div>
                    <div class="fw-medium">{{ $client->ventes_count }}</div>
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
                        <tr>
                            <td>{{ $ecriture->created_at->format('d/m/Y H:i') }}</td>
                            <td><span class="badge {{ $ecriture->type->classeBadge() }}">{{ $ecriture->type->libelle() }}</span></td>
                            <td>
                                @if ($ecriture->reference instanceof \App\Models\Vente)
                                    <a href="{{ route('ventes.ticket', $ecriture->reference) }}">{{ $ecriture->reference->numero }}</a>
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
        <div class="card-body d-flex justify-content-between align-items-center">
            <h3 class="h6 mb-0">Ventes</h3>
            <a href="{{ route('clients.ventes.excel', $client) }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-file-earmark-excel me-1"></i>Exporter en Excel
            </a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Numéro</th>
                        <th>Date</th>
                        <th>Magasin</th>
                        <th>Statut</th>
                        <th class="text-end">Total</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($ventes as $vente)
                        <tr>
                            <td><code>{{ $vente->numero }}</code></td>
                            <td>{{ $vente->created_at->format('d/m/Y H:i') }}</td>
                            <td>{{ $vente->magasin->nom }}</td>
                            <td>
                                @if ($vente->trashed())
                                    <span class="badge text-bg-danger">Annulée</span>
                                @else
                                    <span class="badge text-bg-success">Validée</span>
                                @endif
                            </td>
                            <td class="text-end">{{ number_format($vente->total_net, 0, ',', ' ') }} F</td>
                            <td class="text-end">
                                <a href="{{ route('ventes.ticket', $vente) }}" class="btn btn-sm btn-icon btn-outline-secondary" title="Détail de la vente">
                                    <i class="bi bi-eye"></i>
                                    <span class="visually-hidden">Détail</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-secondary py-4">Aucune vente pour ce client.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($ventes->hasPages())
            <div class="card-body">
                {{ $ventes->onEachSide(1)->links() }}
            </div>
        @endif
    </div>

    <div class="card mb-3">
        <div class="card-body d-flex justify-content-between align-items-center">
            <h3 class="h6 mb-0">Devis</h3>
            <a href="{{ route('clients.devis.excel', $client) }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-file-earmark-excel me-1"></i>Exporter en Excel
            </a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Numéro</th>
                        <th>Date</th>
                        <th>Magasin</th>
                        <th>Statut</th>
                        <th>Valide jusqu'au</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($devis as $unDevis)
                        <tr>
                            <td><code>{{ $unDevis->numero }}</code></td>
                            <td>{{ $unDevis->created_at->format('d/m/Y H:i') }}</td>
                            <td>{{ $unDevis->magasin->nom }}</td>
                            <td><span class="badge {{ $unDevis->statutEffectif()->classeBadge() }}">{{ $unDevis->statutEffectif()->libelle() }}</span></td>
                            <td>{{ $unDevis->date_validite->format('d/m/Y') }}</td>
                            <td class="text-end">
                                <a href="{{ route('devis.show', $unDevis) }}" class="btn btn-sm btn-icon btn-outline-secondary" title="Détail du devis">
                                    <i class="bi bi-eye"></i>
                                    <span class="visually-hidden">Détail</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-secondary py-4">Aucun devis pour ce client.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($devis->hasPages())
            <div class="card-body">
                {{ $devis->onEachSide(1)->links() }}
            </div>
        @endif
    </div>

    <div class="mt-3">
        <a href="{{ route('clients.index') }}" class="btn btn-link ps-0">Retour à la liste</a>
    </div>
@endsection
