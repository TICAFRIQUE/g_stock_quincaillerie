@extends('layouts.app')

@section('title', 'Nouvel utilisateur')

@section('content')
    <h2 class="h4 mb-3">Nouvel utilisateur</h2>

    <div class="card mx-auto" style="max-width: 560px;">
        <div class="card-body">
            <form method="POST" action="{{ route('utilisateurs.store') }}">
                @csrf
                @include('utilisateurs._form')
            </form>
        </div>
    </div>
@endsection
