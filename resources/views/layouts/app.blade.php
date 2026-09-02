<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script>window.DEVISE_ABREVIATION = @json(\App\Models\Devise::abreviationActuelle());</script>
    <title>@yield('title', 'Tableau de bord') — {{ $parametre->nom }}</title>
    <link rel="icon" href="{{ $parametre->logoUrl() }}">
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>
<body>
    <div class="d-flex flex-column min-vh-100">
        <nav class="navbar navbar-expand-lg erp-navbar px-3 d-print-none">
            <div class="container-fluid px-0">
                <a class="navbar-brand d-flex align-items-center gap-2 me-3" href="{{ route('dashboard') }}">
                    <span class="brand-logo-chip">
                        <img src="{{ $parametre->logoUrl() }}" alt="Logo {{ $parametre->nom }}">
                    </span>
                    <span class="d-none d-sm-block">
                        <span class="fs-6 fw-semibold text-white d-block lh-sm">{{ $parametre->nom }}</span>
                        @if ($parametre->slogan)
                            <span class="small d-block lh-sm" style="color: var(--erp-sidebar-color);">{{ $parametre->slogan }}</span>
                        @endif
                    </span>
                </a>

                <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#menuPrincipal"
                        aria-controls="menuPrincipal" aria-label="Ouvrir le menu">
                    <span class="navbar-toggler-icon"></span>
                </button>

                {{-- Desktop (≥ lg) : barre horizontale classique, jamais de sidebar. --}}
                <div class="d-none d-lg-flex align-items-center flex-grow-1">
                    @include('partials.topnav')

                    <div class="d-flex ms-lg-auto">
                        @include('partials.topbar')
                    </div>
                </div>
            </div>
        </nav>

        {{-- Mobile/tablette (< lg) : même menu, ouvert en sidebar coulissante
             plutôt que replié en ligne — plus confortable à parcourir sur un
             petit écran qu'un empilement sous la barre. --}}
        <div class="offcanvas offcanvas-start erp-sidebar d-lg-none" tabindex="-1" id="menuPrincipal" aria-labelledby="menuPrincipalLabel">
            <div class="offcanvas-header">
                <span id="menuPrincipalLabel" class="fw-semibold text-white">Menu</span>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Fermer"></button>
            </div>
            <div class="offcanvas-body d-flex flex-column">
                @include('partials.topnav')

                <hr class="border-secondary-subtle">

                @include('partials.topbar')
            </div>
        </div>

        <main class="flex-grow-1 p-3 p-lg-4 erp-main">
            @if (session('succes'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('succes') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
                </div>
            @endif

            @if (session('erreur'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('erreur') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
                </div>
            @endif

            @if ($errors->any())
                {{-- Échec de validation "brut" (règles required/exists/...), distinct
                     des exceptions métier attrapées ci-dessus (session('erreur')) —
                     sans ce bloc, un formulaire refusé se réaffichait sans aucune
                     explication visible. --}}
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <div class="fw-medium mb-1">Le formulaire contient des erreurs :</div>
                    <ul class="mb-0 ps-3">
                        @foreach ($errors->all() as $erreurValidation)
                            <li>{{ $erreurValidation }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    @include('partials.confirm-delete-modal')
    @include('partials.confirm-action-modal')

    @stack('scripts')
</body>
</html>
