@extends('layouts.app')

@section('title', 'Modifier le type de client')

@section('content')
    <div class="d-flex align-items-center gap-2 mb-3">
        <x-bouton-retour :route="route('type-clients.index')" />
        <h2 class="h4 mb-0">Modifier « {{ $typeClient->nom }} »</h2>
    </div>

    <div class="card mx-auto" style="max-width: 480px;">
        <div class="card-body">
            <form method="POST" action="{{ route('type-clients.update', $typeClient) }}">
                @csrf
                @method('PUT')
                @include('type-clients._form')
            </form>
        </div>
    </div>
@endsection
