@extends('layouts.app')

@section('title', 'Nouveau rôle')

@section('content')
    <h2 class="h4 mb-3">Nouveau rôle</h2>

    <form method="POST" action="{{ route('roles.store') }}">
        @csrf
        @include('roles._form')
    </form>
@endsection
