@extends('layouts.app')

@section('title', 'Nouveau type de client')

@section('content')
    <h2 class="h4 mb-3">Nouveau type de client</h2>

    <div class="card" style="max-width: 480px;">
        <div class="card-body">
            <form method="POST" action="{{ route('type-clients.store') }}">
                @csrf
                @include('type-clients._form')
            </form>
        </div>
    </div>
@endsection
