<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Connexion — G-Stock Vaisselle</title>
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>
<body class="d-flex align-items-center" style="min-height: 100vh; background-color: var(--erp-sidebar-bg);">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-sm-8 col-md-5 col-lg-4">
                <div class="text-center mb-4">
                    <span class="fs-1">🍽️</span>
                    <h1 class="h4 text-white mt-2">G-Stock Vaisselle</h1>
                </div>

                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <h2 class="h5 mb-3">Connexion</h2>

                        @if ($errors->any())
                            <div class="alert alert-danger">
                                @foreach ($errors->all() as $erreur)
                                    <div>{{ $erreur }}</div>
                                @endforeach
                            </div>
                        @endif

                        <form method="POST" action="{{ route('login') }}">
                            @csrf

                            <div class="mb-3">
                                <label for="email" class="form-label">Adresse e-mail</label>
                                <input type="email" name="email" id="email" class="form-control" value="{{ old('email') }}" required autofocus>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">Mot de passe</label>
                                <input type="password" name="password" id="password" class="form-control" required>
                            </div>

                            <div class="form-check mb-3">
                                <input type="checkbox" name="remember" id="remember" class="form-check-input">
                                <label for="remember" class="form-check-label">Se souvenir de moi</label>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">Se connecter</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
