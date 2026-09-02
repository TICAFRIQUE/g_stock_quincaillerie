@extends('layouts.app')

@section('title', 'Modifier la devise')

@section('content')
    <h2 class="h4 mb-3">Modifier la devise</h2>

    <div class="card mx-auto" style="max-width: 480px;">
        <div class="card-body">
            <form method="POST" action="{{ route('devises.update', $devise) }}">
                @csrf
                @method('PUT')
                @include('devises._form')
            </form>
        </div>
    </div>
@endsection
