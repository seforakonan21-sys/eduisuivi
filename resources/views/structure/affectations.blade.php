@extends('layouts.app')
@section('titre', 'Affectations')

@section('contenu')
@include('structure._nav')
<div class="carte">
    <h2 style="margin-top:0">Affectations des enseignants</h2>
    @include('structure._messages')

    <form method="POST" action="{{ route('structure.affectations.store') }}" class="grille">
        @csrf
        <div>
            <label>Classe</label>
            <select name="classe_id" required>
                @foreach($classes as $c)
                    <option value="{{ $c->id }}" @selected(old('classe_id') == $c->id)>{{ $c->nom }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label>Matière</label>
            <select name="matiere_id" required>
                @foreach($matieres as $m)
                    <option value="{{ $m->id }}" @selected(old('matiere_id') == $m->id)>{{ $m->nom }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label>Enseignant</label>
            <select name="enseignant_id" required>
                @foreach($enseignants as $e)
                    <option value="{{ $e->id }}" @selected(old('enseignant_id') == $e->id)>{{ $e->name }}</option>
                @endforeach
            </select>
        </div>
        <div><label>Coefficient</label><input type="number" name="coefficient" step="0.5" min="0.5" max="20" value="{{ old('coefficient', 1) }}" required></div>
        <div><button class="btn" type="submit">Affecter</button></div>
    </form>

    <table>
        <tr><th>Classe</th><th>Matière</th><th>Enseignant</th><th>Coef.</th><th></th></tr>
        @forelse($affectations as $a)
            <tr>
                <td>{{ $a->classe->nom }}</td>
                <td>{{ $a->matiere->nom }}</td>
                <td>{{ $a->enseignant->name }}</td>
                <td>{{ rtrim(rtrim(number_format($a->coefficient, 2, ',', ''), '0'), ',') }}</td>
                <td>
                    <form method="POST" action="{{ route('structure.affectations.destroy', $a) }}" onsubmit="return confirm('Supprimer cette affectation ?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-noir" type="submit">Supprimer</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="5">Aucune affectation pour le moment.</td></tr>
        @endforelse
    </table>
</div>
@endsection