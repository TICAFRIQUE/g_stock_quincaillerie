@extends('layouts.app')

@section('title', 'Clôturer la session')

@section('content')
    <h2 class="h4 mb-3">Clôturer la session — {{ $session->caisse->nom }}</h2>

    <div class="card" style="max-width: 480px;">
        <div class="card-body">
            <p class="mb-1">Fond de caisse : <strong>{{ number_format($session->fond_de_caisse, 0, ',', ' ') }} F</strong></p>
            <p class="mb-3">Théorique (fond + ventes espèces) : <strong>{{ number_format($theorique, 0, ',', ' ') }} F</strong></p>

            <form method="POST" action="{{ route('sessions.cloturer', $session) }}">
                @csrf

                <div class="mb-4">
                    <label for="montant_compte" class="form-label">Montant compté dans le tiroir (F CFA)<span class="required-marker">*</span></label>
                    <input type="number" name="montant_compte" id="montant_compte" class="form-control @error('montant_compte') is-invalid @enderror"
                           value="{{ old('montant_compte', $theorique) }}" min="0" required autofocus>
                    @error('montant_compte') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <button type="submit" class="btn btn-primary">Clôturer</button>
                <a href="{{ route('sessions.show', $session) }}" class="btn btn-link">Annuler</a>
            </form>
        </div>
    </div>
@endsection
