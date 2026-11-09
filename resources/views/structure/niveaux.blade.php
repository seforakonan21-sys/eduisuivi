@extends('layouts.app')
@section('titre', 'Niveaux')

@section('contenu')
@include('structure._nav')
<div class="carte">
    <h2 style="margin-top:0">Niveaux d'enseignement</h2>
    @include('structure._messages')

    <form method="POST" action="{{ route('structure.niveaux.store') }}" class="grille">
        @csrf
        <div><label>Nom</label><input type="text" name="nom" placeholder="CP1" value="{{ old('nom') }}" required></div>
        <div>
            <label>Cycle</label>
            <select name="cycle" required>
                @foreach(\App\Models\Niveau::CYCLES as $valeur => $libelle)
                    <option value="{{ $valeur }}" @selected(old('cycle') === $valeur)>{{ $libelle }}</option>
                @endforeach
            </select>
        </div>
        <div><label>Ordre</label><input type="number" name="ordre" min="1" max="30" value="{{ old('ordre', 1) }}" required></div>
        <div><button class="btn" type="submit">Ajouter</button></div>
    </form>

    <table>
        <tr><th>Ordre</th><th>Niveau</th><th>Cycle</th><th>Classes</th><th></th></tr>
        @forelse($niveaux as $n)
            <tr>
                <td>{{ $n->ordre }}</td>
                <td>{{ $n->nom }}</td>
                <td>{{ \App\Models\Niveau::CYCLES[$n->cycle] ?? $n->cycle }}</td>
                <td>{{ $n->classes_count }}</td>
                <td>
                    <form method="POST" action="{{ route('structure.niveaux.destroy', $n) }}" onsubmit="return confirm('Supprimer ce niveau ?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-noir" type="submit">Supprimer</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="5">Aucun niveau pour le moment.</td></tr>
        @endforelse
    </table>
</div>
@endsection