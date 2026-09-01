@extends('layouts.app')

@section('title', 'Modifier le magasin')

@section('content')
    <h2 class="h4 mb-3">Modifier « {{ $magasin->nom }} »</h2>

    <div class="card mx-auto" style="max-width: 560px;">
        <div class="card-body">
            <form method="POST" action="{{ route('magasins.update', $magasin) }}">
                @csrf
                @method('PUT')
                @include('magasins._form')
            </form>
        </div>
    </div>
@endsection
