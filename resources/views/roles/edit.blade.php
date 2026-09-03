@extends('layouts.app')

@section('title', 'Modifier le rôle')

@section('content')
    <div class="d-flex align-items-center gap-2 mb-3">
        <x-bouton-retour :route="route('roles.index')" />
        <h2 class="h4 mb-0">Modifier « {{ $role->name }} »</h2>
    </div>

    <form method="POST" action="{{ route('roles.update', $role) }}">
        @csrf
        @method('PUT')
        @include('roles._form')
    </form>
@endsection
