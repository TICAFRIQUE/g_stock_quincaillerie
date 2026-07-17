@extends('layouts.app')

@section('title', 'Nouveau produit')

@section('content')
    <h2 class="h4 mb-3">Nouveau produit</h2>

    <div class="card" style="max-width: 760px;">
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
