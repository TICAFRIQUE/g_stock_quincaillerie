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
                    <div class="fw-medium">Mot de passe</div>
                    <div class="text-secondary small">Génère un nouveau mot de passe et l'envoie par e-mail à l'utilisateur.</div>
                </div>
                <x-confirm-button :action="route('utilisateurs.reinitialiser-mot-de-passe', $utilisateur)"
                    message="Réinitialiser le mot de passe de « {{ $utilisateur->name }} » ? Un nouveau mot de passe sera généré et envoyé à {{ $utilisateur->email }}."
                    button-label="Réinitialiser le mot de passe" button-class="btn-outline-danger" icon="bi-key" />
            </div>
        </div>
    </div>
@endsection
