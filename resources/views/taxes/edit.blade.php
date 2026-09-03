@extends('layouts.app')

@section('title', 'Modifier la taxe')

@section('content')
    <div class="d-flex align-items-center gap-2 mb-3">
        <x-bouton-retour :route="route('taxes.index')" />
        <h2 class="h4 mb-0">Modifier « {{ $taxe->nom }} »</h2>
    </div>

    <div class="card mx-auto" style="max-width: 480px;">
        <div class="card-body">
            <form method="POST" action="{{ route('taxes.update', $taxe) }}">
                @csrf
                @method('PUT')
                @include('taxes._form')
            </form>
        </div>
    </div>
@endsection
