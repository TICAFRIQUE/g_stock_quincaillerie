@extends('layouts.app')

@section('title', 'Nouvelle caisse')

@section('content')
    <div class="d-flex align-items-center gap-2 mb-3">
        <x-bouton-retour :route="route('caisses.index')" />
        <h2 class="h4 mb-0">Nouvelle caisse</h2>
    </div>

    <div class="card mx-auto" style="max-width: 480px;">
        <div class="card-body">
            <form method="POST" action="{{ route('caisses.store') }}">
                @csrf
                @include('caisses._form')
            </form>
        </div>
    </div>
@endsection
