@extends('layouts.app')
@section('titre', 'Matières')

@section('contenu')
@include('structure._nav')
<div class="carte">
    <h2 style="margin-top:0">Matières</h2>
    @include('structure._messages')

    <form method="POST" action="{{ route('structure.matieres.store') }}" class="grille">
        @csrf
        <div><label>Nom</label><input type="text" name="nom" placeholder="Mathématiques" value="{{ old('nom') }}" required></div>
        <div><label>Code</label><input type="text" name="code" placeholder="MATH" maxlength="10" value="{{ old('code') }}" required></div>
        <div><button class="btn" type="submit">Ajouter</button></div>
    </form>

    <table>
        <tr><th>Code</th><th>Matière</th><th></th></tr>
        @forelse($matieres as $m)
            <tr>
                <td>{{ $m->code }}</td>
                <td>{{ $m->nom }}</td>
                <td>
                    <form method="POST" action="{{ route('structure.matieres.destroy', $m) }}" onsubmit="return confirm('Supprimer cette matière ?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-noir" type="submit">Supprimer</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="3">Aucune matière pour le moment.</td></tr>
        @endforelse
    </table>
</div>
@endsection