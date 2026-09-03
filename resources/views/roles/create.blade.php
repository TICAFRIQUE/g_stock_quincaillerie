@extends('layouts.app')

@section('title', 'Nouveau rôle')

@section('content')
    <div class="d-flex align-items-center gap-2 mb-3">
        <x-bouton-retour :route="route('roles.index')" />
        <h2 class="h4 mb-0">Nouveau rôle</h2>
    </div>

    <form method="POST" action="{{ route('roles.store') }}">
        @csrf
        @include('roles._form')
    </form>
@endsection
