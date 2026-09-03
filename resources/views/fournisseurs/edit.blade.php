@extends('layouts.app')

@section('title', 'Modifier le fournisseur')

@section('content')
    <div class="d-flex align-items-center gap-2 mb-3">
        <x-bouton-retour :route="route('fournisseurs.index')" />
        <h2 class="h4 mb-0">Modifier « {{ $fournisseur->nom }} »</h2>
    </div>

    <div class="card mx-auto" style="max-width: 560px;">
        <div class="card-body">
            <form method="POST" action="{{ route('fournisseurs.update', $fournisseur) }}">
                @csrf
                @method('PUT')
                @include('fournisseurs._form')
            </form>
        </div>
    </div>
@endsection
