@extends('layouts.app')

@section('title', 'Nouveau moyen de paiement')

@section('content')
    <h2 class="h4 mb-3">Nouveau moyen de paiement</h2>

    <div class="card" style="max-width: 480px;">
        <div class="card-body">
            <form method="POST" action="{{ route('moyens-paiement.store') }}">
                @csrf
                <div class="mb-3">
                    <label for="nom" class="form-label">Nom<span class="required-marker">*</span></label>
                    <input type="text" name="nom" id="nom" class="form-control @error('nom') is-invalid @enderror"
                           value="{{ old('nom') }}" required autofocus>
                    @error('nom') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <button type="submit" class="btn btn-primary">Enregistrer</button>
                <a href="{{ route('moyens-paiement.index') }}" class="btn btn-link">Annuler</a>
            </form>
        </div>
    </div>
@endsection
