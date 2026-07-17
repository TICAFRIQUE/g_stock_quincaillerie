@extends('layouts.app')

@section('title', 'Modifier le rôle')

@section('content')
    <h2 class="h4 mb-3">Modifier « {{ $role->name }} »</h2>

    <form method="POST" action="{{ route('roles.update', $role) }}">
        @csrf
        @method('PUT')
        @include('roles._form')
    </form>
@endsection
