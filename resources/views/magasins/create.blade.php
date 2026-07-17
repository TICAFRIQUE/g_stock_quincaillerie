@extends('layouts.app')

@section('title', 'Nouveau magasin')

@section('content')
    <h2 class="h4 mb-3">Nouveau magasin</h2>

    <div class="card" style="max-width: 560px;">
        <div class="card-body">
            <form method="POST" action="{{ route('magasins.store') }}">
                @csrf
                @include('magasins._form')
            </form>
        </div>
    </div>
@endsection
