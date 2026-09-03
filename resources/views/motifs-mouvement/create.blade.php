@extends('layouts.app')

@section('title', 'Nouveau motif')

@section('content')
    <div class="d-flex align-items-center gap-2 mb-3">
        <x-bouton-retour :route="route('motifs-mouvement.index')" />
        <h2 class="h4 mb-0">Nouveau motif</h2>
    </div>

    <div class="card mx-auto" style="max-width: 480px;">
        <div class="card-body">
            <form method="POST" action="{{ route('motifs-mouvement.store') }}">
                @csrf
                @include('motifs-mouvement._form')
            </form>
        </div>
    </div>
@endsection
