@extends('layouts.app')

@section('title', 'Modifier la taxe')

@section('content')
    <h2 class="h4 mb-3">Modifier « {{ $taxe->nom }} »</h2>

    <div class="card mx-auto" style="max-width: 480px;">
        <div class="card-body">
            <form method="POST" action="{{ route('taxes.update', $taxe) }}">
                @csrf
                @method('PUT')
                @include('taxes._form')
            </form>
        </div>
    </div>
@endsection
