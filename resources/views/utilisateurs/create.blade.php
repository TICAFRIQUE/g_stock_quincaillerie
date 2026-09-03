@extends('layouts.app')

@section('title', 'Nouvel utilisateur')

@section('content')
    <div class="d-flex align-items-center gap-2 mb-3">
        <x-bouton-retour :route="route('utilisateurs.index')" />
        <h2 class="h4 mb-0">Nouvel utilisateur</h2>
    </div>

    <div class="card mx-auto" style="max-width: 560px;">
        <div class="card-body">
            <form method="POST" action="{{ route('utilisateurs.store') }}">
                @csrf
                @include('utilisateurs._form')
            </form>
        </div>
    </div>
@endsection
