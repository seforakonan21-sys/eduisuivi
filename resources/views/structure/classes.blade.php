@extends('layouts.app')
@section('titre', 'Classes')

@section('contenu')
@include('structure._nav')
<div class="carte">
    <h2 style="margin-top:0">Classes</h2>
    @include('structure._messages')

    <form method="POST" action="{{ route('structure.classes.store') }}" class="grille">
        @csrf
        <div>
            <label>Année scolaire</label>
            <select name="annee_scolaire_id" required>
                @foreach($annees as $a)
                    <option value="{{ $a->id }}" @selected(old('annee_scolaire_id') == $a->id)>{{ $a->libelle }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label>Niveau</label>
            <select name="niveau_id" required>
                @foreach($niveaux as $n)
                    <option value="{{ $n->id }}" @selected(old('niveau_id') == $n->id)>{{ $n->nom }}</option>
                @endforeach
            </select>
        </div>
        <div><label>Nom de la classe</label><input type="text" name="nom" placeholder="6ème A" value="{{ old('nom') }}" required></div>
        <div><label>Effectif max</label><input type="number" name="effectif_max" min="1" max="200" value="{{ old('effectif_max') }}"></div>
        <div><button class="btn" type="submit">Ajouter</button></div>
    </form>

    <table>
        <tr><th>Classe</th><th>Niveau</th><th>Année</th><th>Effectif max</th><th></th></tr>
        @forelse($classes as $c)
            <tr>
                <td>{{ $c->nom }}</td>
                <td>{{ $c->niveau->nom }}</td>
                <td>{{ $c->anneeScolaire->libelle }}</td>
                <td>{{ $c->effectif_max ?? '-' }}</td>
                <td>
                    <form method="POST" action="{{ route('structure.classes.destroy', $c) }}" onsubmit="return confirm('Supprimer cette classe ?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-noir" type="submit">Supprimer</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="5">Aucune classe pour le moment.</td></tr>
        @endforelse
    </table>
</div>
@endsection