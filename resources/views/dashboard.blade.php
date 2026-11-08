@extends('layouts.app')
@section('titre', 'Tableau de bord - EduSuivi')

@section('contenu')
<div class="carte">
    <h2 style="margin-top:0">Bienvenue, {{ $user->name }}</h2>
    <p><span class="badge">{{ $user->role->label() }}</span></p>
    @if($user->etablissement)
        <p>Établissement : <strong>{{ $user->etablissement->nom }}</strong></p>
    @else
        <p>Vue globale de la plateforme.</p>
    @endif

    @if($user->aLeRole(\App\Enums\Role::AdminGeneral))
        <a class="btn btn-noir" href="{{ route('administration') }}">Administration</a>
    @endif
</div>
@endsection