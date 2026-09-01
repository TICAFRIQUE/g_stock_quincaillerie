@extends('layouts.app')

@section('title', 'Nouveau motif')

@section('content')
    <h2 class="h4 mb-3">Nouveau motif</h2>

    <div class="card mx-auto" style="max-width: 480px;">
        <div class="card-body">
            <form method="POST" action="{{ route('motifs-mouvement.store') }}">
                @csrf
                @include('motifs-mouvement._form')
            </form>
        </div>
    </div>
@endsection
