@extends('layouts.app')

@section('title', 'Tableau de bord')

@section('content')
    <div class="mb-4">
        <h2 class="h4">Bonjour, {{ $utilisateur->name }} 👋</h2>
        <p class="text-secondary mb-0">{{ $utilisateur->magasin->nom }}</p>
    </div>

    @include('dashboard._apercu')
@endsection
