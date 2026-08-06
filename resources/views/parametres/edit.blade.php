@extends('layouts.app')

@section('title', 'Paramètres')

@section('content')
    <h2 class="h4 mb-3">Paramètres</h2>

    <div class="row g-4">
        <div class="col-12 col-lg-7">
            <div class="card">
                <div class="card-body">
                    <h3 class="h6 mb-3">Identité de l'application</h3>

                    <img src="{{ $parametre->logoUrl() }}" alt="Logo actuel" class="rounded mb-3 d-block" style="max-height: 120px;">

                    <form method="POST" action="{{ route('parametres.update') }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="logo" class="form-label">Logo</label>
                            <input type="file" name="logo" id="logo" accept="image/*"
                                   class="form-control @error('logo') is-invalid @enderror">
                            <div class="form-text">Remplace le logo affiché dans l'application (menu, connexion, e-mails).</div>
                            @error('logo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label for="nom" class="form-label">Nom<span class="required-marker">*</span></label>
                            <input type="text" name="nom" id="nom" class="form-control @error('nom') is-invalid @enderror"
                                   value="{{ old('nom', $parametre->nom) }}" required>
                            @error('nom') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label for="slogan" class="form-label">Slogan</label>
                            <input type="text" name="slogan" id="slogan" class="form-control @error('slogan') is-invalid @enderror"
                                   value="{{ old('slogan', $parametre->slogan) }}">
                            @error('slogan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label for="numero" class="form-label">Numéro de téléphone</label>
                            <input type="text" name="numero" id="numero" class="form-control @error('numero') is-invalid @enderror"
                                   value="{{ old('numero', $parametre->numero) }}">
                            @error('numero') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label for="adresse" class="form-label">Adresse</label>
                            <input type="text" name="adresse" id="adresse" class="form-control @error('adresse') is-invalid @enderror"
                                   value="{{ old('adresse', $parametre->adresse) }}">
                            @error('adresse') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-4">
                            <label for="duree_validite_devis_jours" class="form-label">Durée de validité d'un devis (jours)<span class="required-marker">*</span></label>
                            <input type="number" name="duree_validite_devis_jours" id="duree_validite_devis_jours" min="1" max="365"
                                   class="form-control @error('duree_validite_devis_jours') is-invalid @enderror"
                                   value="{{ old('duree_validite_devis_jours', $parametre->duree_validite_devis_jours) }}" required>
                            <div class="form-text">Passé ce délai, un devis non transformé passe automatiquement au statut « Expiré ».</div>
                            @error('duree_validite_devis_jours') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-5">
            <div class="card">
                <div class="card-body">
                    <h3 class="h6">Sauvegarde de la base de données</h3>
                    <p class="text-secondary small">
                        Télécharge un export complet (mysqldump) de la base de données, à conserver en lieu sûr.
                    </p>
                    <form method="POST" action="{{ route('parametres.backup') }}" data-telechargement>
                        @csrf
                        <button type="submit" class="btn btn-outline-primary">
                            <i class="bi bi-download me-1"></i>Télécharger une sauvegarde
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
