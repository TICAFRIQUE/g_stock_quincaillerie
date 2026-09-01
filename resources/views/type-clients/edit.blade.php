@extends('layouts.app')

@section('title', 'Modifier le type de client')

@section('content')
    <h2 class="h4 mb-3">Modifier « {{ $typeClient->nom }} »</h2>

    <div class="card mx-auto" style="max-width: 480px;">
        <div class="card-body">
            <form method="POST" action="{{ route('type-clients.update', $typeClient) }}">
                @csrf
                @method('PUT')
                @include('type-clients._form')
            </form>
        </div>
    </div>
@endsection
