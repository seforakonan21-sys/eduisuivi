@extends('layouts.app')
@section('titre', 'Élèves')

@section('contenu')
@include('structure._nav')
<div class="carte">
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px">
        <h2 style="margin:0">Élèves</h2>
        <a class="btn" href="{{ route('eleves.create') }}">Nouvel élève</a>
    </div>

    <div style="margin-top:14px">@include('structure._messages')</div>

    <form method="GET" action="{{ route('eleves.index') }}" class="grille">
        <div>
            <label>Rechercher</label>
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Nom, prénoms ou matricule">
        </div>
        <div><button class="btn btn-noir" type="submit">Rechercher</button></div>
    </form>

    <table>
        <tr><th>Matricule</th><th>Nom et prénoms</th><th>Sexe</th><th>Classe</th><th>Statut</th><th></th></tr>
        @forelse($eleves as $e)
            @php $ins = $e->inscriptions->first(); @endphp
            <tr>
                <td>{{ $e->matricule }}</td>
                <td>{{ $e->nomComplet() }}</td>
                <td>{{ $e->sexe }}</td>
                <td>{{ $ins?->classe->nom ?? '-' }}</td>
                <td>@if($ins)<span class="badge">{{ \App\Models\Inscription::STATUTS[$ins->statut] }}</span>@endif</td>
                <td><a href="{{ route('eleves.show', $e) }}">Voir le dossier</a></td>
            </tr>
        @empty
            <tr><td colspan="6">Aucun élève trouvé.</td></tr>
        @endforelse
    </table>

    <div style="margin-top:14px; display:flex; gap:14px; align-items:center">
        @if($eleves->onFirstPage())
            <span style="color:#999">← Précédent</span>
        @else
            <a href="{{ $eleves->previousPageUrl() }}">← Précédent</a>
        @endif

        <span>Page {{ $eleves->currentPage() }} sur {{ $eleves->lastPage() }}</span>

        @if($eleves->hasMorePages())
            <a href="{{ $eleves->nextPageUrl() }}">Suivant →</a>
        @else
            <span style="color:#999">Suivant →</span>
        @endif
    </div>
</div>
@endsection