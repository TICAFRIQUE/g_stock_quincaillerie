@extends('layouts.app')

@section('title', 'Clients')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h4 mb-0">Clients</h2>
        @can('client.gerer')
            <a href="{{ route('clients.create') }}" class="btn btn-primary">Nouveau client</a>
        @endcan
    </div>

    <x-recherche-form :action="route('clients.index')" placeholder="Nom ou téléphone…" />

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <x-th-tri champ="nom" label="Nom" />
                        <th>Type</th>
                        <th>Téléphone</th>
                        <th>Solde dû</th>
                        <th>Limite de crédit</th>
                        <x-th-tri champ="actif" label="Statut" />
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($clients as $client)
                        @php $solde = (int) ($soldes[$client->id] ?? 0); @endphp
                        <tr>
                            <td><a href="{{ route('clients.show', $client) }}">{{ $client->nom }}</a></td>
                            <td>{{ $client->typeClient->nom ?? '—' }}</td>
                            <td>{{ $client->telephone ?? '—' }}</td>
                            <td class="{{ $solde > 0 ? 'text-danger fw-medium' : '' }}">{{ number_format($solde, 0, ',', ' ') }} F</td>
                            <td>{{ $client->limite_credit !== null ? number_format($client->limite_credit, 0, ',', ' ').' F' : 'Illimitée' }}</td>
                            <td>
                                @if ($client->actif)
                                    <span class="badge text-bg-success">Actif</span>
                                @else
                                    <span class="badge text-bg-secondary">Inactif</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @can('client.gerer')
                                    <x-edit-button :href="route('clients.edit', $client)" />
                                    <x-delete-button :action="route('clients.destroy', $client)" :label="'le client « '.$client->nom.' »'" />
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-secondary py-4">Aucun client pour l'instant.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $clients->links() }}
    </div>
@endsection
