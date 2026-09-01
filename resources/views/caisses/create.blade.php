@extends('layouts.app')

@section('title', 'Nouvelle caisse')

@section('content')
    <h2 class="h4 mb-3">Nouvelle caisse</h2>

    <div class="card mx-auto" style="max-width: 480px;">
        <div class="card-body">
            <form method="POST" action="{{ route('caisses.store') }}">
                @csrf
                @include('caisses._form')
            </form>
        </div>
    </div>
@endsection
