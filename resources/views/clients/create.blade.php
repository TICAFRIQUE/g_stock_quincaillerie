@extends('layouts.app')

@section('title', 'Nouveau client')

@section('content')
    <h2 class="h4 mb-3">Nouveau client</h2>

    <div class="card" style="max-width: 560px;">
        <div class="card-body">
            <form method="POST" action="{{ route('clients.store') }}">
                @csrf
                @include('clients._form')
            </form>
        </div>
    </div>
@endsection
