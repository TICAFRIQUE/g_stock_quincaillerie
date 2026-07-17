@extends('layouts.app')

@section('title', 'Nouvel inventaire')

@section('content')
    <h2 class="h4 mb-3">Nouvel inventaire</h2>

    <div class="card" style="max-width: 480px;">
        <div class="card-body">
            <form method="POST" action="{{ route('inventaires.store') }}">
                @csrf

                <div class="mb-3">
                    <label for="magasin_id" class="form-label">Magasin<span class="required-marker">*</span></label>
                    <select name="magasin_id" id="magasin_id" class="form-select @error('magasin_id') is-invalid @enderror" required>
                        <option value="">— Choisir —</option>
                        @foreach ($magasins as $magasin)
                            <option value="{{ $magasin->id }}" @selected(old('magasin_id') == $magasin->id)>{{ $magasin->nom }}</option>
                        @endforeach
                    </select>
                    @error('magasin_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-4">
                    <label for="date" class="form-label">Date<span class="required-marker">*</span></label>
                    <input type="date" name="date" id="date" class="form-control @error('date') is-invalid @enderror"
                           value="{{ old('date', now()->toDateString()) }}" required>
                    @error('date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <button type="submit" class="btn btn-primary">Créer la fiche</button>
                <a href="{{ route('inventaires.index') }}" class="btn btn-link">Annuler</a>
            </form>
        </div>
    </div>
@endsection
