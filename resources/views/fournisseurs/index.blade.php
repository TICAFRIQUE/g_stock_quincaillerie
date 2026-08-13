@extends('layouts.app')

@section('title', 'Fournisseurs')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h4 mb-0">Fournisseurs</h2>
        @can('fournisseur.gerer')
            <a href="{{ route('fournisseurs.create') }}" class="btn btn-primary">Nouveau fournisseur</a>
        @endcan
    </div>

    <x-recherche-form :action="route('fournisseurs.index')" placeholder="Nom, téléphone ou e-mail…" />

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <x-th-tri champ="nom" label="Nom" />
                        <th>Téléphone</th>
                        <th>E-mail</th>
                        <th>Solde dû</th>
                        <x-th-tri champ="actif" label="Statut" />
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($fournisseurs as $fournisseur)
                        @php $solde = (int) ($soldes[$fournisseur->id] ?? 0); @endphp
                        <tr>
                            <td><a href="{{ route('fournisseurs.show', $fournisseur) }}">{{ $fournisseur->nom }}</a></td>
                            <td>{{ $fournisseur->telephone ?? '—' }}</td>
                            <td>{{ $fournisseur->email ?? '—' }}</td>
                            <td class="{{ $solde > 0 ? 'text-danger fw-medium' : '' }}">{{ number_format($solde, 0, ',', ' ') }} F</td>
                            <td>
                                @if ($fournisseur->actif)
                                    <span class="badge text-bg-success">Actif</span>
                                @else
                                    <span class="badge text-bg-secondary">Inactif</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @can('fournisseur.gerer')
                                    <x-edit-button :href="route('fournisseurs.edit', $fournisseur)" />
                                    <x-delete-button :action="route('fournisseurs.destroy', $fournisseur)" :label="'le fournisseur « '.$fournisseur->nom.' »'" />
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-secondary py-4">Aucun fournisseur pour l'instant.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $fournisseurs->links() }}
    </div>
@endsection
