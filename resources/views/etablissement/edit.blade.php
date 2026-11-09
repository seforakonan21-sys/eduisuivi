@extends('layouts.app')
@section('titre', 'Mon établissement')

@section('contenu')
<div class="carte">
    <h2 style="margin-top:0">{{ $etablissement->nom }}</h2>
    <p style="color:var(--discret)">Ces informations apparaissent sur les bulletins.</p>
    @include('structure._messages')

    <form method="POST" action="{{ route('etablissement.update') }}" enctype="multipart/form-data" class="grille">
        @csrf @method('PUT')
        <div><label>Adresse</label><input type="text" name="adresse" value="{{ old('adresse', $etablissement->adresse) }}"></div>
        <div><label>Téléphone</label><input type="text" name="telephone" value="{{ old('telephone', $etablissement->telephone) }}"></div>
        <div><label>E-mail</label><input type="email" name="email" value="{{ old('email', $etablissement->email) }}"></div>
        <div><label>Nom du signataire (directeur)</label><input type="text" name="signataire_nom" value="{{ old('signataire_nom', $etablissement->signataire_nom) }}"></div>

        <div>
            <label>Logo (PNG ou JPG, 1 Mo max)</label>
            @if($etablissement->logo)
                <img src="{{ asset('storage/' . $etablissement->logo) }}" alt="Logo" style="height:60px; display:block; margin-bottom:8px">
            @endif
            <input type="file" name="logo" accept="image/png,image/jpeg">
        </div>
        <div>
            <label>Cachet et signature (PNG ou JPG, 1 Mo max)</label>
            @if($etablissement->cachet)
                <img src="{{ asset('storage/' . $etablissement->cachet) }}" alt="Cachet" style="height:60px; display:block; margin-bottom:8px">
            @endif
            <input type="file" name="cachet" accept="image/png,image/jpeg">
        </div>

        <div><button class="btn" type="submit">Enregistrer</button></div>
    </form>
</div>
@endsection