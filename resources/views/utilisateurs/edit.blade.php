@extends('layouts.app')

@section('title', 'Modifier l\'utilisateur')

@section('content')
    <h2 class="h4 mb-3">Modifier « {{ $utilisateur->name }} »</h2>

    <div class="card" style="max-width: 560px;">
        <div class="card-body">
            <form method="POST" action="{{ route('utilisateurs.update', $utilisateur) }}">
                @csrf
                @method('PUT')
                @include('utilisateurs._form')
            </form>

            <hr>

            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="fw-medium">Code de connexion</div>
                    <div class="text-secondary small">Génère un nouveau code à 4 chiffres, affiché à l'écran une seule fois.</div>
                </div>
                <x-confirm-button :action="route('utilisateurs.reinitialiser-code', $utilisateur)"
                    message="Réinitialiser le code de « {{ $utilisateur->name }} » ? Un nouveau code à 4 chiffres sera généré."
                    button-label="Réinitialiser le code" button-class="btn-outline-danger" icon="bi-key" />
            </div>
        </div>
    </div>
@endsection
