@extends('layouts.app')

@section('title', 'Nouvelle devise')

@section('content')
    <div class="d-flex align-items-center gap-2 mb-3">
        <x-bouton-retour :route="route('devises.index')" />
        <h2 class="h4 mb-0">Nouvelle devise</h2>
    </div>

    <div class="card mx-auto" style="max-width: 480px;">
        <div class="card-body">
            <form method="POST" action="{{ route('devises.store') }}">
                @csrf
                @include('devises._form')
            </form>
        </div>
    </div>
@endsection
