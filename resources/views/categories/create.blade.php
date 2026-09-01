@extends('layouts.app')

@section('title', 'Nouvelle catégorie')

@section('content')
    <h2 class="h4 mb-3">Nouvelle catégorie</h2>

    <div class="card mx-auto" style="max-width: 480px;">
        <div class="card-body">
            <form method="POST" action="{{ route('categories.store') }}">
                @csrf
                @include('categories._form')
            </form>
        </div>
    </div>
@endsection
