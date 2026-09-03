@extends('layouts.app')

@section('title', "Ouvrir une session")

@section('content')
    <div class="d-flex align-items-center gap-2 mb-3">
        <x-bouton-retour :route="route('sessions.index')" />
        <h2 class="h4 mb-0">Ouvrir une session — {{ $caisse->nom }}</h2>
    </div>

    <div class="card mx-auto" style="max-width: 480px;">
        <div class="card-body">
            <form method="POST" action="{{ route('sessions.store', $caisse) }}">
                @csrf

                <div class="mb-4">
                    <label for="fond_de_caisse" class="form-label">Fond de caisse (F CFA)<span class="required-marker">*</span></label>
                    <input type="number" name="fond_de_caisse" id="fond_de_caisse" class="form-control @error('fond_de_caisse') is-invalid @enderror"
                           value="{{ old('fond_de_caisse', 0) }}" min="0" required autofocus>
                    @error('fond_de_caisse') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <div class="form-text">Montant en espèces présent dans le tiroir au démarrage.</div>
                </div>

                <button type="submit" class="btn btn-primary">Ouvrir la session</button>
                <a href="{{ route('sessions.index') }}" class="btn btn-link">Annuler</a>
            </form>
        </div>
    </div>
@endsection
