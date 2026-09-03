@extends('layouts.app')

@section('title', 'Nouveau magasin')

@section('content')
    <div class="d-flex align-items-center gap-2 mb-3">
        <x-bouton-retour :route="route('magasins.index')" />
        <h2 class="h4 mb-0">Nouveau magasin</h2>
    </div>

    <div class="card mx-auto" style="max-width: 560px;">
        <div class="card-body">
            <form method="POST" action="{{ route('magasins.store') }}">
                @csrf
                @include('magasins._form')
            </form>
        </div>
    </div>
@endsection
