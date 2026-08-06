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
    <div class="d-flex" x-data="{ sidebarOpen: false }">
        <div class="erp-sidebar-backdrop" :class="{ 'erp-sidebar-open': sidebarOpen }" @click="sidebarOpen = false"></div>

        <aside class="erp-sidebar flex-shrink-0 p-3" :class="{ 'erp-sidebar-open': sidebarOpen }">
            <div class="d-flex align-items-center gap-2 mb-4 px-2">
                <span class="brand-logo-chip">
                    <img src="{{ $parametre->logoUrl() }}" alt="Logo {{ $parametre->nom }}">
                </span>
                <div>
                    <span class="fs-6 fw-semibold text-white d-block lh-sm">{{ $parametre->nom }}</span>
                    @if ($parametre->slogan)
                        <span class="small d-block lh-sm" style="color: var(--erp-sidebar-color);">{{ $parametre->slogan }}</span>
                    @endif
                    <span class="brand-accent-bar mt-1"></span>
                </div>
            </div>
            @include('partials.sidebar')
        </aside>

        <div class="flex-grow-1 erp-main d-flex flex-column">
            <header class="erp-topbar d-flex align-items-center justify-content-between px-3 py-2">
                <button class="btn btn-light d-lg-none" type="button" @click="sidebarOpen = !sidebarOpen">
                    <span aria-hidden="true">&#9776;</span>
                    <span class="visually-hidden">Ouvrir le menu</span>
                </button>
                <h1 class="h5 mb-0 d-none d-lg-block">@yield('title', 'Tableau de bord')</h1>
                @include('partials.topbar')
            </header>

            <main class="flex-grow-1 p-3 p-lg-4">
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
    </div>

    @include('partials.confirm-delete-modal')
    @include('partials.confirm-action-modal')

    @stack('scripts')
</body>
</html>
