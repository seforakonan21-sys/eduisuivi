@extends('layouts.app')
@section('titre', 'Appréciations par matière')

@section('contenu')
<div class="carte">
    <h2 style="margin-top:0">Appréciations par matière</h2>
    @include('structure._messages')

    @if($affectations->isEmpty())
        <div class="alerte">Aucune matière ne vous est affectée pour l'année en cours.</div>
    @else
        <form method="GET" action="{{ route('appreciations.edit') }}" class="grille" style="margin-bottom:0">
            <div>
                <label>Classe et matière</label>
                <select name="affectation_id" required>
                    @foreach($affectations as $a)
                        <option value="{{ $a->id }}" @selected(request('affectation_id') == $a->id)>{{ $a->classe->nom }} · {{ $a->matiere->nom }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label>Période</label>
                <select name="periode_id" required>
                    @foreach($periodes as $p)
                        <option value="{{ $p->id }}" @selected(request('periode_id') == $p->id)>{{ $p->nom }}</option>
                    @endforeach
                </select>
            </div>
            <div><button class="btn" type="submit">Afficher</button></div>
        </form>
    @endif
</div>

@if($affectation)
<div class="carte">
    <h3 style="margin-top:0">{{ $affectation->classe->nom }} · {{ $affectation->matiere->nom }} · {{ $periode->nom }}</h3>

    <form method="POST" action="{{ route('appreciations.update') }}">
        @csrf @method('PUT')
        <input type="hidden" name="affectation_id" value="{{ $affectation->id }}">
        <input type="hidden" name="periode_id" value="{{ $periode->id }}">

        <table>
            <tr><th>Élève</th><th>Appréciation (255 caractères max)</th></tr>
            @forelse($eleves as $e)
                <tr>
                    <td style="white-space:nowrap">{{ $e->nomComplet() }}</td>
                    <td><input type="text" name="appreciations[{{ $e->id }}]" maxlength="255"
                               value="{{ $textes[$e->id] ?? '' }}" placeholder="Ex. : Bon travail, continue ainsi."></td>
                </tr>
            @empty
                <tr><td colspan="2">Aucun élève inscrit dans cette classe.</td></tr>
            @endforelse
        </table>

        @if($eleves->isNotEmpty())
            <button class="btn" type="submit" style="margin-top:14px">Enregistrer les appréciations</button>
        @endif
    </form>
</div>
@endif
@endsection