@extends('layouts.app')

@section('title', 'Ventes en attente')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h4 mb-0">Ventes en attente — {{ $session->caisse->nom }}</h2>
        <a href="{{ route('sessions.show', $session) }}" class="btn btn-link">
            <i class="bi bi-arrow-left me-1"></i>Retour à la session
        </a>
    </div>

    <div class="row g-3">
        @forelse ($ventesEnAttente as $venteEnAttente)
            <div class="col-12 col-md-6">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <h3 class="h6">Panier #{{ $venteEnAttente->id }}</h3>
                            <span class="text-secondary small">{{ $venteEnAttente->created_at->format('d/m/Y H:i') }}</span>
                        </div>
                        @if ($venteEnAttente->libelle)
                            <p class="small text-secondary fst-italic">{{ $venteEnAttente->libelle }}</p>
                        @endif
                        @if ($venteEnAttente->client)
                            <p class="small mb-1">
                                <i class="bi bi-person-fill me-1"></i>{{ $venteEnAttente->client->nom }}
                            </p>
                        @endif
                        <ul class="list-unstyled small mb-3">
                            @foreach ($venteEnAttente->lignes as $ligne)
                                <li>
                                    {{ quantite($ligne->quantite) }} × {{ $ligne->produit->libelle_affichage }}
                                    @if ($ligne->uniteVente) ({{ $ligne->uniteVente->libelle }}) @endif
                                </li>
                            @endforeach
                        </ul>
                        <div class="d-flex gap-2">
                            <a href="{{ route('ventes-en-attente.reprendre.form', $venteEnAttente) }}" class="btn btn-sm btn-primary">
                                <i class="bi bi-arrow-right-circle me-1"></i>Reprendre
                            </a>
                            <x-delete-button :action="route('ventes-en-attente.annuler', $venteEnAttente)" :label="'ce panier en attente'" />
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <p class="text-secondary">Aucune vente en attente sur cette session.</p>
            </div>
        @endforelse
    </div>
@endsection
