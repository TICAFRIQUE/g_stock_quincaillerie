@extends('layouts.erreur')

@section('titre', 'Session expirée')
@section('code', '419')
@section('message', "Votre session a expiré, probablement après un long moment d'inactivité. Reconnectez-vous pour continuer.")
@section('bouton', 'Se reconnecter')
