@extends('layouts.app')

@section('title', 'Caisses')

@section('content')
    <h2 class="h4 mb-3">Caisses</h2>

    <x-alerte-sessions-anciennes :sessions="$sessionsAnciennes" />

    @if ($caissesOccupees->isEmpty() && $caissesLibres->isEmpty())
        <p class="text-secondary">Aucune caisse disponible.</p>
    @endif

    @if ($caissesOccupees->isNotEmpty())
        <h3 class="h6 text-secondary text-uppercase small mb-2">
            <i class="bi bi-lock-fill me-1"></i>Caisses occupées
        </h3>
        <div class="row g-3 mb-4">
            @foreach ($caissesOccupees as $caisse)
                @include('sessions._carte-caisse', ['caisse' => $caisse])
            @endforeach
        </div>
    @endif

    @if ($caissesLibres->isNotEmpty())
        <h3 class="h6 text-secondary text-uppercase small mb-2">
            <i class="bi bi-unlock-fill me-1"></i>Caisses libres
        </h3>
        <div class="row g-3">
            @foreach ($caissesLibres as $caisse)
                @include('sessions._carte-caisse', ['caisse' => $caisse])
            @endforeach
        </div>
    @endif
@endsection
