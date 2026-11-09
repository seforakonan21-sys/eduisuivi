@extends('layouts.app')
@section('titre', 'Tableau de bord')

@section('contenu')
<div class="carte">
    <h2 style="margin-bottom:6px">Bienvenue, {{ $user->name }}</h2>
    <span class="badge">{{ $user->role->label() }}</span>

    @if($annee)
        <p style="margin:14px 0 0">Année scolaire en cours : <strong>{{ $annee->libelle }}</strong></p>
    @elseif($user->aLeRole(\App\Enums\Role::Directeur, \App\Enums\Role::Administratif))
        <div class="alerte" style="margin:14px 0 0">
            Aucune année scolaire active.
            <a href="{{ route('structure.annees.index') }}">Créer une année</a> pour commencer.
        </div>
    @endif
</div>

@if($stats)
    <div class="stats">
        @foreach($stats as $libelle => $valeur)
            <div class="stat">
                <div class="valeur">{{ $valeur }}</div>
                <div class="libelle">{{ $libelle }}</div>
            </div>
        @endforeach
    </div>
@endif
@endsection