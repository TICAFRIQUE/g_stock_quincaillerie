@extends('layouts.app')

@section('title', 'Nouveau fournisseur')

@section('content')
    <h2 class="h4 mb-3">Nouveau fournisseur</h2>

    <div class="card mx-auto" style="max-width: 560px;">
        <div class="card-body">
            <form method="POST" action="{{ route('fournisseurs.store') }}">
                @csrf
                @include('fournisseurs._form')
            </form>
        </div>
    </div>
@endsection
