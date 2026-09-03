@extends('layouts.app')

@section('title', 'Nouveau produit')

@section('content')
    <div class="d-flex align-items-center gap-2 mb-3">
        <x-bouton-retour :route="route('produits.index')" />
        <h2 class="h4 mb-0">Nouveau produit</h2>
    </div>

    <div class="card mx-auto" style="max-width: 1000px;">
        <div class="card-body">
            <form method="POST" action="{{ route('produits.store') }}" enctype="multipart/form-data">
                @csrf
                @include('produits._form')
                <button type="submit" class="btn btn-primary">Enregistrer</button>
                <a href="{{ route('produits.index') }}" class="btn btn-link">Annuler</a>
            </form>
        </div>
    </div>
@endsection
