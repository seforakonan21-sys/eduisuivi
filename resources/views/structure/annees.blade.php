@extends('layouts.app')
@section('titre', 'Années scolaires')

@section('contenu')
@include('structure._nav')
<div class="carte">
    <h2 style="margin-top:0">Années scolaires</h2>
    @include('structure._messages')

    <form method="POST" action="{{ route('structure.annees.store') }}" class="grille">
        @csrf
        <div><label>Libellé</label><input type="text" name="libelle" placeholder="2026-2027" value="{{ old('libelle') }}" required></div>
        <div><label>Début</label><input type="date" name="date_debut" value="{{ old('date_debut') }}" required></div>
        <div><label>Fin</label><input type="date" name="date_fin" value="{{ old('date_fin') }}" required></div>
        <div><label style="font-weight:400"><input type="checkbox" name="est_active" value="1"> Année en cours</label></div>
        <div><button class="btn" type="submit">Ajouter</button></div>
    </form>

    <table>
        <tr><th>Libellé</th><th>Début</th><th>Fin</th><th>Statut</th><th></th></tr>
        @forelse($annees as $a)
            <tr>
                <td>{{ $a->libelle }}</td>
                <td>{{ $a->date_debut->format('d/m/Y') }}</td>
                <td>{{ $a->date_fin->format('d/m/Y') }}</td>
                <td>@if($a->est_active)<span class="badge">En cours</span>@endif</td>
                <td>
                    <form method="POST" action="{{ route('structure.annees.destroy', $a) }}" onsubmit="return confirm('Supprimer cette année ?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-noir" type="submit">Supprimer</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="5">Aucune année scolaire pour le moment.</td></tr>
        @endforelse
    </table>
</div>
@endsection