@extends('layouts.app')
@section('titre', 'Nouvel élève')

@section('contenu')
@include('structure._nav')
<div class="carte">
    <h2 style="margin-top:0">Inscrire un nouvel élève</h2>
    @include('structure._messages')

    @if(! $annee)
        <div class="alerte">
            Aucune année scolaire n'est active. <a href="{{ route('structure.annees.index') }}">Créez-en une</a> avant d'inscrire des élèves.
        </div>
    @elseif($classes->isEmpty())
        <div class="alerte">
            L'année {{ $annee->libelle }} n'a pas encore de classe. <a href="{{ route('structure.classes.index') }}">Créez une classe</a> d'abord.
        </div>
    @else
        <p>Année scolaire : <strong>{{ $annee->libelle }}</strong>. Le matricule sera généré automatiquement.</p>

        <form method="POST" action="{{ route('eleves.store') }}" enctype="multipart/form-data" class="grille">
            @csrf
            <div><label>Nom</label><input type="text" name="nom" value="{{ old('nom') }}" required></div>
            <div><label>Prénoms</label><input type="text" name="prenoms" value="{{ old('prenoms') }}" required></div>
            <div><label>Date de naissance</label><input type="date" name="date_naissance" value="{{ old('date_naissance') }}" required></div>
            <div>
                <label>Sexe</label>
                <select name="sexe" required>
                    <option value="F" @selected(old('sexe') === 'F')>Féminin</option>
                    <option value="M" @selected(old('sexe') === 'M')>Masculin</option>
                </select>
            </div>
            <div>
                <label>Classe</label>
                <select name="classe_id" required>
                    @foreach($classes as $c)
                        <option value="{{ $c->id }}" @selected(old('classe_id') == $c->id)>{{ $c->nom }}</option>
                    @endforeach
                </select>
            </div>
            <div><label>Téléphone</label><input type="text" name="telephone" value="{{ old('telephone') }}"></div>
            <div><label>Adresse</label><input type="text" name="adresse" value="{{ old('adresse') }}"></div>
            <div><label>Photo (facultative, 2 Mo max)</label><input type="file" name="photo" accept="image/*"></div>
            <div><button class="btn" type="submit">Enregistrer et inscrire</button></div>
        </form>
    @endif
</div>
@endsection