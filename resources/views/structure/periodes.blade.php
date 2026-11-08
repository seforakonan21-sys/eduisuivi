@extends('layouts.app')
@section('titre', 'Périodes')

@section('contenu')
@include('structure._nav')
<div class="carte">
    <h2 style="margin-top:0">Périodes scolaires</h2>
    @include('structure._messages')

    <form method="POST" action="{{ route('structure.periodes.store') }}" class="grille">
        @csrf
        <div>
            <label>Année scolaire</label>
            <select name="annee_scolaire_id" required>
                @foreach($annees as $a)
                    <option value="{{ $a->id }}" @selected(old('annee_scolaire_id') == $a->id)>{{ $a->libelle }}</option>
                @endforeach
            </select>
        </div>
        <div><label>Nom</label><input type="text" name="nom" placeholder="Trimestre 1" value="{{ old('nom') }}" required></div>
        <div><label>Ordre</label><input type="number" name="ordre" min="1" max="6" value="{{ old('ordre', 1) }}" required></div>
        <div><label>Début</label><input type="date" name="date_debut" value="{{ old('date_debut') }}" required></div>
        <div><label>Fin</label><input type="date" name="date_fin" value="{{ old('date_fin') }}" required></div>
        <div><button class="btn" type="submit">Ajouter</button></div>
    </form>

    @forelse($annees as $a)
        <h3>{{ $a->libelle }} @if($a->est_active)<span class="badge">En cours</span>@endif</h3>
        <table>
            <tr><th>Ordre</th><th>Nom</th><th>Début</th><th>Fin</th><th></th></tr>
            @forelse($a->periodes as $p)
                <tr>
                    <td>{{ $p->ordre }}</td>
                    <td>{{ $p->nom }}</td>
                    <td>{{ $p->date_debut->format('d/m/Y') }}</td>
                    <td>{{ $p->date_fin->format('d/m/Y') }}</td>
                    <td>
                        <form method="POST" action="{{ route('structure.periodes.destroy', $p) }}" onsubmit="return confirm('Supprimer cette période ?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-noir" type="submit">Supprimer</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5">Aucune période pour cette année.</td></tr>
            @endforelse
        </table>
    @empty
        <p>Crée d'abord une année scolaire.</p>
    @endforelse
</div>
@endsection