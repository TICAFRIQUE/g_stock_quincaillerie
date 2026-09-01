@extends('layouts.app')

@section('title', 'Nouvelle taxe')

@section('content')
    <h2 class="h4 mb-3">Nouvelle taxe</h2>

    <div class="card mx-auto" style="max-width: 480px;">
        <div class="card-body">
            <form method="POST" action="{{ route('taxes.store') }}">
                @csrf
                @include('taxes._form')
            </form>
        </div>
    </div>
@endsection
