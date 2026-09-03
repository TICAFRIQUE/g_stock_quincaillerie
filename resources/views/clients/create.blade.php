@extends('layouts.app')

@section('title', 'Nouveau client')

@section('content')
    <div class="d-flex align-items-center gap-2 mb-3">
        <x-bouton-retour :route="route('clients.index')" />
        <h2 class="h4 mb-0">Nouveau client</h2>
    </div>

    <div class="card mx-auto" style="max-width: 560px;">
        <div class="card-body">
            <form method="POST" action="{{ route('clients.store') }}">
                @csrf
                @include('clients._form')
            </form>
        </div>
    </div>
@endsection
