@extends('layouts.app')

@section('title', 'Tableau de bord')

@section('content')
    <div class="mb-4">
        <h2 class="h4">Bonjour, {{ $utilisateur->name }} 👋</h2>
        <p class="text-secondary mb-0">Vue consolidée — tous magasins</p>
    </div>

    @include('dashboard._apercu')

    <div class="card">
        <div class="card-body">
            <h3 class="h6">Comparatif par magasin (ce mois)</h3>
            <table class="table table-sm mb-0">
                <thead>
                    <tr>
                        <th>Magasin</th>
                        <th>Chiffre d'affaires</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($comparatifMagasins as $magasin)
                        <tr>
                            <td>{{ $magasin->nom }}</td>
                            <td>{{ number_format($magasin->ventes_sum_total_net ?? 0, 0, ',', ' ') }} F</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="text-center text-secondary py-3">Aucun magasin.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
