@extends('layouts.app')

@section('title', 'Modifier l\'unité')

@section('content')
    <h2 class="h4 mb-3">Modifier « {{ $unite->nom }} »</h2>

    <div class="card mx-auto" style="max-width: 480px;">
        <div class="card-body">
            <form method="POST" action="{{ route('unites.update', $unite) }}">
                @csrf
                @method('PUT')
                <div class="mb-3">
                    <label for="nom" class="form-label">Nom<span class="required-marker">*</span></label>
                    <input type="text" name="nom" id="nom" class="form-control @error('nom') is-invalid @enderror"
                           value="{{ old('nom', $unite->nom) }}" required autofocus>
                    @error('nom') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label for="abbreviation" class="form-label">Abréviation</label>
                    <input type="text" name="abbreviation" id="abbreviation" class="form-control @error('abbreviation') is-invalid @enderror"
                           value="{{ old('abbreviation', $unite->abbreviation) }}" placeholder="Ex. L, m, kg" maxlength="10">
                    @error('abbreviation') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <div class="form-text">Optionnelle — utilisée à la place du nom complet dans les écrans compacts (vente, ticket). Laissez vide si non pertinent (ex. Carton, Bidon).</div>
                </div>
                <x-actif-toggle :checked="$unite->actif" />
                <button type="submit" class="btn btn-primary">Enregistrer</button>
                <a href="{{ route('unites.index') }}" class="btn btn-link">Annuler</a>
            </form>
        </div>
    </div>
@endsection
