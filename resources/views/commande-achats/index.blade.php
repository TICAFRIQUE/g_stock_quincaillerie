@extends('layouts.app')

@section('title', "Bons d'achat")

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h4 mb-0">Bons d'achat</h2>
        @can('achat.creer')
            <a href="{{ route('commande-achats.create') }}" class="btn btn-primary">Nouveau bon d'achat</a>
        @endcan
    </div>

    <x-recherche-form :action="route('commande-achats.index')" placeholder="Numéro de commande…"
        :autres-params="['date_debut', 'date_fin', 'statut']">
        <div>
            <label for="date_debut" class="form-label small mb-1">Du</label>
            <input type="date" name="date_debut" id="date_debut" class="form-control" value="{{ $dateDebut }}" onchange="this.form.submit()">
        </div>
        <div>
            <label for="date_fin" class="form-label small mb-1">Au</label>
            <input type="date" name="date_fin" id="date_fin" class="form-control" value="{{ $dateFin }}" onchange="this.form.submit()">
        </div>
        <div>
            <label for="statut" class="form-label small mb-1">Statut</label>
            <select name="statut" id="statut" class="form-select" onchange="this.form.submit()">
                <option value="">Tous les statuts</option>
                <option value="brouillon" @selected(request('statut') === 'brouillon')>Brouillon</option>
                <option value="validee" @selected(request('statut') === 'validee')>Validée</option>
            </select>
        </div>
    </x-recherche-form>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <x-th-tri champ="numero" label="Numéro" />
                        <th>Fournisseur</th>
                        <x-th-tri champ="date_commande" label="Date" />
                        <x-th-tri champ="statut" label="Statut" />
                        <th class="text-end">Montant dû</th>
                        <th class="text-end">Déjà réglé</th>
                        <th class="text-end">Reste à régler</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($commandes as $commande)
                        <tr>
                            <td><code>{{ $commande->numero }}</code></td>
                            <td>{{ $commande->fournisseur->nom }}</td>
                            <td>{{ $commande->date_commande->format('d/m/Y') }}</td>
                            <td>
                                @if ($commande->statut === 'validee')
                                    <span class="badge text-bg-success">Validée</span>
                                @else
                                    <span class="badge text-bg-secondary">Brouillon</span>
                                @endif
                            </td>
                            @if ($commande->statut === 'validee')
                                <td class="text-end">{{ montant($commande->totalTtc()) }}</td>
                                <td class="text-end text-success">{{ montant($commande->montantRegle()) }}</td>
                                <td class="text-end {{ $commande->resteDu() > 0 ? 'text-danger fw-medium' : 'text-secondary' }}">{{ montant($commande->resteDu()) }}</td>
                            @else
                                <td class="text-end text-secondary">—</td>
                                <td class="text-end text-secondary">—</td>
                                <td class="text-end text-secondary">—</td>
                            @endif
                            <td class="text-end">
                                <a href="{{ route('commande-achats.show', $commande) }}" class="btn btn-sm btn-icon btn-outline-secondary" title="Voir">
                                    <i class="bi bi-eye"></i>
                                    <span class="visually-hidden">Voir</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-secondary py-4">Aucun bon d'achat pour l'instant.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $commandes->links() }}
    </div>
@endsection
