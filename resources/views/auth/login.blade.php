<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Connexion — G-Stock Vaisselle</title>
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>
<body>
    <div class="login-layout">
        <div class="login-layout__vitrine">
            <div class="login-layout__vitrine-contenu">
                <span class="fs-1">🍽️</span>
                <h1 class="h2 text-white mt-3 mb-3">G-Stock Vaisselle</h1>
                <p class="lead text-white-50">
                    Bienvenue dans votre application de gestion de caisse et de stock —
                    suivez vos ventes, vos magasins et vos équipes en toute simplicité,
                    et en toute sécurité.
                </p>

                <ul class="login-layout__atouts list-unstyled mt-4">
                    <li>
                        <i class="bi bi-boxes"></i>
                        <div>
                            <strong>Stock toujours à jour</strong>
                            <div class="text-white-50 small">Chaque mouvement est tracé, rien n'est jamais écrasé.</div>
                        </div>
                    </li>
                    <li>
                        <i class="bi bi-cash-coin"></i>
                        <div>
                            <strong>Caisse et ventes en temps réel</strong>
                            <div class="text-white-50 small">Une session par caisse, un ticket par vente.</div>
                        </div>
                    </li>
                    <li>
                        <i class="bi bi-shop"></i>
                        <div>
                            <strong>Plusieurs magasins, une seule application</strong>
                            <div class="text-white-50 small">Consolidez tous vos points de vente au même endroit.</div>
                        </div>
                    </li>
                </ul>
            </div>

            <div class="login-layout__illustration">
                @include('auth.partials.illustration-pos')
            </div>
        </div>

        <div class="login-layout__formulaire">
            <div class="login-layout__carte">
                <h2 class="h4 mb-1">Connexion</h2>
                <p class="text-secondary small mb-4">Accédez à votre espace de gestion.</p>

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

                    <div class="mb-4" x-data="{ visible: false }">
                        <label for="password" class="form-label">Mot de passe</label>
                        <div class="input-group">
                            <input :type="visible ? 'text' : 'password'" name="password" id="password" class="form-control" required>
                            <button type="button" class="btn btn-outline-secondary" @click="visible = !visible"
                                    :title="visible ? 'Masquer le mot de passe' : 'Afficher le mot de passe'">
                                <i class="bi" :class="visible ? 'bi-eye-slash' : 'bi-eye'"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Se connecter</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
