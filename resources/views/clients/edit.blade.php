@extends('layouts.app')

@section('title', 'Modifier le client')

@section('content')
    <h2 class="h4 mb-3">Modifier « {{ $client->nom }} »</h2>

    <div class="card mx-auto" style="max-width: 560px;">
        <div class="card-body">
            <form method="POST" action="{{ route('clients.update', $client) }}">
                @csrf
                @method('PUT')
                @include('clients._form')
            </form>
        </div>
    </div>
@endsection
