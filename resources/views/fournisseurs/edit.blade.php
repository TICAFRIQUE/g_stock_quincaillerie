@extends('layouts.app')

@section('title', 'Modifier le fournisseur')

@section('content')
    <h2 class="h4 mb-3">Modifier « {{ $fournisseur->nom }} »</h2>

    <div class="card mx-auto" style="max-width: 560px;">
        <div class="card-body">
            <form method="POST" action="{{ route('fournisseurs.update', $fournisseur) }}">
                @csrf
                @method('PUT')
                @include('fournisseurs._form')
            </form>
        </div>
    </div>
@endsection
