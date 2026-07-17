@extends('layouts.app')

@section('title', 'Modifier la caisse')

@section('content')
    <h2 class="h4 mb-3">Modifier « {{ $caisse->nom }} »</h2>

    <div class="card" style="max-width: 480px;">
        <div class="card-body">
            <form method="POST" action="{{ route('caisses.update', $caisse) }}">
                @csrf
                @method('PUT')
                @include('caisses._form')
            </form>
        </div>
    </div>
@endsection
