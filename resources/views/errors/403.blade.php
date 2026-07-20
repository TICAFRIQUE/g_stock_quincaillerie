@extends('layouts.erreur')

@section('titre', 'Accès refusé')
@section('code', '403')
@section('message', $exception->getMessage() ?: "Vous n'avez pas l'autorisation d'accéder à cette page.")
