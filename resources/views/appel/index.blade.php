@extends('layouts.app')
@section('titre', 'Appel du jour')

@section('contenu')
<div class="carte">
    <h2 style="margin-top:0">Appel</h2>
    @include('structure._messages')

    @if($classes->isEmpty())
        <div class="alerte">Aucune classe disponible pour l'année en cours.</div>
    @else
        <form method="GET" action="{{ route('appel.index') }}" class="grille" style="margin-bottom:0">
            <div>
                <label>Classe</label>
                <select name="classe_id" required>
                    @foreach($classes as $c)
                        <option value="{{ $c->id }}" @selected(request('classe_id') == $c->id)>{{ $c->nom }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label>Date</label>
                <input type="date" name="date" value="{{ $date }}" max="{{ now()->toDateString() }}" required>
            </div>
            <div><button class="btn" type="submit">Afficher la liste</button></div>
        </form>
    @endif
</div>

@if($classe)
<div class="carte">
    <h3 style="margin-top:0">{{ $classe->nom }} · {{ \Illuminate\Support\Carbon::parse($date)->format('d/m/Y') }}</h3>

    @if($appelFait)
        <div class="alerte">L'appel de cette date est déjà enregistré : vous pouvez le corriger.</div>
    @endif

    <form method="POST" action="{{ route('appel.store') }}">
        @csrf
        <input type="hidden" name="classe_id" value="{{ $classe->id }}">
        <input type="hidden" name="date" value="{{ $date }}">

        <table>
            <tr><th>Matricule</th><th>Élève</th><th>Présence</th><th style="width:120px">Retard (min)</th></tr>
            @forelse($eleves as $e)
                @php
                    $abs = $existantes[$e->id] ?? null;
                    $statut = $abs->type ?? 'present';
                @endphp
                <tr>
                    <td>{{ $e->matricule }}</td>
                    <td>{{ $e->nomComplet() }}</td>
                    <td>
                        @foreach(['present' => 'Présent', 'absence' => 'Absent', 'retard' => 'Retard'] as $valeur => $libelle)
                            <label style="display:inline-flex; gap:4px; margin:0 14px 0 0; font-weight:400">
                                <input type="radio" name="statuts[{{ $e->id }}]" value="{{ $valeur }}" @checked($statut === $valeur)>
                                {{ $libelle }}
                            </label>
                        @endforeach
                    </td>
                    <td>
                        <input type="number" name="minutes[{{ $e->id }}]" min="1" max="240" placeholder="min"
                               value="{{ $abs->duree_minutes ?? '' }}">
                    </td>
                </tr>
            @empty
                <tr><td colspan="4">Aucun élève inscrit dans cette classe.</td></tr>
            @endforelse
        </table>

        @if($eleves->isNotEmpty())
            <p style="color:var(--discret); font-size:.88rem">Les élèves marqués « Présent » ne génèrent aucune absence. Les parents des absents et des retardataires sont prévenus.</p>
            <button class="btn" type="submit">Enregistrer l'appel</button>
        @endif
    </form>
</div>
@endif
@endsection