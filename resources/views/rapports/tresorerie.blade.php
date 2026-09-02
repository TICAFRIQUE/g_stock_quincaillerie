@extends('layouts.app')

@section('title', 'Mouvements de trésorerie')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-1">
        <h2 class="h4 mb-0">Mouvements de trésorerie</h2>
        <div class="d-flex gap-2 flex-wrap">
            <x-bouton-imprimer :pdf-route="route('rapports.tresorerie.pdf', request()->query())" />
            <a href="{{ route('rapports.tresorerie.pdf', request()->query()) }}" class="btn btn-outline-secondary d-print-none">
                <i class="bi bi-file-earmark-pdf me-1"></i>PDF
            </a>
            <a href="{{ route('rapports.tresorerie.excel', request()->query()) }}" class="btn btn-outline-secondary d-print-none">
                <i class="bi bi-file-earmark-excel me-1"></i>Excel
            </a>
        </div>
    </div>
    <p class="text-secondary small mb-3">
        Caisse Générale et comptes bancaires/autres — indépendant des tiroirs de caissiers
        (voir <a href="{{ route('rapports.mouvements-caisse') }}">Mouvements de caisse</a>).
    </p>
    @php
        $filtresActifs = collect([
            request('compte_tresorerie_id') ? 'Compte : ' . $comptes->firstWhere('id', (int) request('compte_tresorerie_id'))?->nom : null,
            request('type') ? 'Type : ' . \App\Enums\EcritureCompteTresorerieType::tryFrom(request('type'))?->libelle() : null,
        ])->filter();
    @endphp
    <p class="text-secondary small mb-3">
        Période : du {{ $debut->format('d/m/Y') }} au {{ $fin->format('d/m/Y') }}
        @if ($filtresActifs->isNotEmpty())
            · {{ $filtresActifs->implode(' · ') }}
        @endif
    </p>

    <form method="GET" action="{{ route('rapports.tresorerie') }}" class="row g-2 mb-3 d-print-none">
        <div class="col-auto">
            <input type="date" name="debut" value="{{ $debut->toDateString() }}" class="form-control form-control-sm">
        </div>
        <div class="col-auto">
            <input type="date" name="fin" value="{{ $fin->toDateString() }}" class="form-control form-control-sm">
        </div>
        <div class="col-auto">
            <select name="compte_tresorerie_id" class="form-select form-select-sm">
                <option value="">Tous les comptes</option>
                @foreach ($comptes as $compte)
                    <option value="{{ $compte->id }}" @selected(request('compte_tresorerie_id') == $compte->id)>{{ $compte->nom }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-auto">
            <select name="type" class="form-select form-select-sm">
                <option value="">Tous les types</option>
                @foreach (\App\Enums\EcritureCompteTresorerieType::cases() as $type)
                    <option value="{{ $type->value }}" @selected(request('type') === $type->value)>{{ $type->libelle() }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-sm btn-primary">Filtrer</button>
        </div>
        @if (request()->hasAny(['debut', 'fin', 'compte_tresorerie_id', 'type']))
            <div class="col-auto">
                <a href="{{ route('rapports.tresorerie') }}" class="btn btn-sm btn-outline-danger" title="Réinitialiser les filtres">
                    <i class="bi bi-x-circle me-1"></i>Réinitialiser
                </a>
            </div>
        @endif
    </form>

    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
            <x-kpi-card label="Mouvements" icon="bi-arrow-left-right" color="primary"
                :value="$nombre" />
        </div>
        <div class="col-6 col-md-3">
            <x-kpi-card label="Total entrées" icon="bi-box-arrow-in-down" color="success"
                :value="montant($totalEntrees)" />
        </div>
        <div class="col-6 col-md-3">
            <x-kpi-card label="Total sorties" icon="bi-box-arrow-up" color="danger"
                :value="montant($totalSorties)" />
        </div>
        <div class="col-6 col-md-3">
            <x-kpi-card label="Solde net" icon="bi-wallet2" :color="$soldeNet >= 0 ? 'success' : 'danger'"
                :value="($soldeNet > 0 ? '+ ' : ($soldeNet < 0 ? '− ' : '')) . montant(abs($soldeNet))" />
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Compte</th>
                        <th>Type</th>
                        <th>Motif</th>
                        <th class="text-end">Montant</th>
                        <th>Auteur</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($ecritures as $ecriture)
                        <tr>
                            <td>{{ $ecriture->created_at->format('d/m/Y H:i') }}</td>
                            <td>{{ $ecriture->compteTresorerie->nom }}</td>
                            <td><span class="badge {{ $ecriture->type->classeBadge() }}">{{ $ecriture->type->libelle() }}</span></td>
                            <td>{{ $ecriture->motif ?? '—' }}</td>
                            <td class="text-end fw-medium {{ $ecriture->montant >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ $ecriture->montant >= 0 ? '+ ' : '− ' }}{{ montant(abs($ecriture->montant)) }}
                            </td>
                            <td>{{ $ecriture->auteur?->name ?? 'Utilisateur supprimé' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-secondary py-4">Aucun mouvement de trésorerie sur cette période.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($ecritures instanceof \Illuminate\Pagination\LengthAwarePaginator)
        <div class="mt-3 d-print-none">
            {{ $ecritures->links() }}
        </div>
    @endif
@endsection
