@extends('layouts.app')

@section('title', 'Modifier le motif')

@section('content')
    <h2 class="h4 mb-3">Modifier « {{ $motif->nom }} »</h2>

    <div class="card" style="max-width: 480px;">
        <div class="card-body">
            <form method="POST" action="{{ route('motifs-mouvement.update', $motif) }}">
                @csrf
                @method('PUT')
                @include('motifs-mouvement._form')
            </form>
        </div>
    </div>
@endsection
