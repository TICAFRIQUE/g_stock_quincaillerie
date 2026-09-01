@extends('layouts.app')

@section('title', 'Modifier le moyen de paiement')

@section('content')
    <h2 class="h4 mb-3">Modifier « {{ $moyenPaiement->nom }} »</h2>

    <div class="card mx-auto" style="max-width: 480px;">
        <div class="card-body">
            <form method="POST" action="{{ route('moyens-paiement.update', $moyenPaiement) }}">
                @csrf
                @method('PUT')
                <div class="mb-3">
                    <label for="nom" class="form-label">Nom<span class="required-marker">*</span></label>
                    <input type="text" name="nom" id="nom" class="form-control @error('nom') is-invalid @enderror"
                           value="{{ old('nom', $moyenPaiement->nom) }}" required autofocus>
                    @error('nom') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <x-actif-toggle :checked="$moyenPaiement->actif" />
                <button type="submit" class="btn btn-primary">Enregistrer</button>
                <a href="{{ route('moyens-paiement.index') }}" class="btn btn-link">Annuler</a>
            </form>
        </div>
    </div>
@endsection
