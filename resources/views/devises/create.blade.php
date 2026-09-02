@extends('layouts.app')

@section('title', 'Nouvelle devise')

@section('content')
    <h2 class="h4 mb-3">Nouvelle devise</h2>

    <div class="card mx-auto" style="max-width: 480px;">
        <div class="card-body">
            <form method="POST" action="{{ route('devises.store') }}">
                @csrf
                @include('devises._form')
            </form>
        </div>
    </div>
@endsection
