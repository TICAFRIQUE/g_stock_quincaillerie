<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Tableau de bord') — {{ $parametre->nom }}</title>
    <link rel="icon" type="image/jpeg" href="{{ $parametre->logoUrl() }}">
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

                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuPrincipal"
                        aria-controls="menuPrincipal" aria-expanded="false" aria-label="Ouvrir le menu">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="menuPrincipal">
                    @include('partials.topnav')

                    <div class="d-flex mt-3 mt-lg-0 ms-lg-auto">
                        @include('partials.topbar')
                    </div>
                </div>
            </div>
        </nav>

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

            @yield('content')
        </main>
    </div>

    @include('partials.confirm-delete-modal')
    @include('partials.confirm-action-modal')

    @stack('scripts')
</body>
</html>
