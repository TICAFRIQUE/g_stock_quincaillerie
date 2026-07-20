@extends('layouts.app')

@section('title', 'Rapports')

@section('content')
    <h2 class="h4 mb-3">Rapports</h2>

    <div class="row g-3">
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card h-100">
                <div class="card-body">
                    <h3 class="h6"><i class="bi bi-receipt me-2"></i>Ventes</h3>
                    <p class="text-secondary small">Par période, magasin, caissier.</p>
                    <a href="{{ route('rapports.ventes') }}" class="btn btn-sm btn-outline-primary">Voir</a>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card h-100">
                <div class="card-body">
                    <h3 class="h6"><i class="bi bi-graph-up-arrow me-2"></i>Marge</h3>
                    <p class="text-secondary small">Prix de vente − coût appliqué.</p>
                    <a href="{{ route('rapports.marge') }}" class="btn btn-sm btn-outline-primary">Voir</a>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card h-100">
                <div class="card-body">
                    <h3 class="h6"><i class="bi bi-boxes me-2"></i>Valeur du stock</h3>
                    <p class="text-secondary small">Au coût moyen pondéré, par magasin.</p>
                    <a href="{{ route('rapports.stock') }}" class="btn btn-sm btn-outline-primary">Voir</a>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card h-100">
                <div class="card-body">
                    <h3 class="h6"><i class="bi bi-cash-coin me-2"></i>Écarts de caisse</h3>
                    <p class="text-secondary small">Par session, par période.</p>
                    <a href="{{ route('rapports.ecarts-caisse') }}" class="btn btn-sm btn-outline-primary">Voir</a>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card h-100">
                <div class="card-body">
                    <h3 class="h6"><i class="bi bi-x-octagon me-2"></i>Casse / pertes</h3>
                    <p class="text-secondary small">Pièces perdues, par produit et magasin.</p>
                    <a href="{{ route('rapports.casse') }}" class="btn btn-sm btn-outline-primary">Voir</a>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card h-100">
                <div class="card-body">
                    <h3 class="h6"><i class="bi bi-clipboard-check me-2"></i>Inventaires</h3>
                    <p class="text-secondary small">Historique des comptages et écarts.</p>
                    <a href="{{ route('rapports.inventaires') }}" class="btn btn-sm btn-outline-primary">Voir</a>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card h-100">
                <div class="card-body">
                    <h3 class="h6"><i class="bi bi-journal-text me-2"></i>Journal d'activité</h3>
                    <p class="text-secondary small">Actions sensibles, connexions/déconnexions.</p>
                    <a href="{{ route('journal.index') }}" class="btn btn-sm btn-outline-primary">Voir</a>
                </div>
            </div>
        </div>
    </div>
@endsection
