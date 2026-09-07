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

                        <div class="mb-4" x-data="{ valeur: '{{ old('couleur_primaire', $parametre->couleur_primaire ?: '#e8590c') }}' }">
                            <label for="couleur_primaire" class="form-label">Couleur primaire</label>
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <input type="color" class="form-control form-control-color" style="width: 3rem;"
                                       x-model="valeur" aria-label="Sélecteur de couleur primaire">
                                <input type="text" name="couleur_primaire" id="couleur_primaire"
                                       class="form-control @error('couleur_primaire') is-invalid @enderror" style="max-width: 9rem;"
                                       x-model="valeur" pattern="^#[0-9A-Fa-f]{6}$" maxlength="7" placeholder="#e8590c">
                                <span class="rounded border flex-shrink-0" style="width: 2.25rem; height: 2.25rem;"
                                      x-bind:style="'background-color: ' + valeur"></span>
                                <button type="button" class="btn btn-outline-secondary btn-sm" @click="valeur = '#e8590c'">
                                    Réinitialiser
                                </button>
                            </div>
                            @error('couleur_primaire') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            <div class="form-text">Remplace l'orange par défaut (boutons, liens, menu, formulaires…).</div>
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

                        <div class="mb-3">
                            <label for="devise_id" class="form-label">Devise</label>
                            <select name="devise_id" id="devise_id" class="form-select @error('devise_id') is-invalid @enderror">
                                @foreach ($devises as $devise)
                                    <option value="{{ $devise->id }}" @selected(old('devise_id', $parametre->devise_id) == $devise->id)>
                                        {{ $devise->nom }} ({{ $devise->abreviation }})
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">
                                Affichage uniquement — les montants restent des nombres entiers, aucune conversion.
                                <a href="{{ route('devises.index') }}">Gérer les devises</a>
                            </div>
                            @error('devise_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
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
