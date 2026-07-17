@extends('layouts.app')

@section('title', 'Modifier la catégorie')

@section('content')
    <h2 class="h4 mb-3">Modifier « {{ $categorie->nom }} »</h2>

    <div class="card" style="max-width: 480px;">
        <div class="card-body">
            <form method="POST" action="{{ route('categories.update', $categorie) }}">
                @csrf
                @method('PUT')
                @include('categories._form')
            </form>
        </div>
    </div>
@endsection
