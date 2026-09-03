@extends('layouts.app')

@section('title', 'Nouvelle unité')

@section('content')
    <div class="d-flex align-items-center gap-2 mb-3">
        <x-bouton-retour :route="route('unites.index')" />
        <h2 class="h4 mb-0">Nouvelle unité</h2>
    </div>

    <div class="card mx-auto" style="max-width: 480px;">
        <div class="card-body">
            <form method="POST" action="{{ route('unites.store') }}">
                @csrf
                <div class="mb-3">
                    <label for="nom" class="form-label">Nom<span class="required-marker">*</span></label>
                    <input type="text" name="nom" id="nom" class="form-control @error('nom') is-invalid @enderror"
                           value="{{ old('nom') }}" placeholder="Ex. Litre, Mètre, Carton…" required autofocus>
                    @error('nom') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label for="abbreviation" class="form-label">Abréviation</label>
                    <input type="text" name="abbreviation" id="abbreviation" class="form-control @error('abbreviation') is-invalid @enderror"
                           value="{{ old('abbreviation') }}" placeholder="Ex. L, m, kg" maxlength="10">
                    @error('abbreviation') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <div class="form-text">Optionnelle — utilisée à la place du nom complet dans les écrans compacts (vente, ticket). Laissez vide si non pertinent (ex. Carton, Bidon).</div>
                </div>
                <button type="submit" class="btn btn-primary">Enregistrer</button>
                <a href="{{ route('unites.index') }}" class="btn btn-link">Annuler</a>
            </form>
        </div>
    </div>
@endsection
