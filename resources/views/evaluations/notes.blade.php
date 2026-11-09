@extends('layouts.app')
@section('titre', 'Saisie des notes')

@section('contenu')
<div class="carte">
    <a href="{{ route('evaluations.index') }}">← Retour aux évaluations</a>
    <h2 style="margin:10px 0 4px">{{ $evaluation->titre }}</h2>
    <p style="margin:0 0 14px; color:var(--discret)">
        {{ $evaluation->affectation->classe->nom }} · {{ $evaluation->affectation->matiere->nom }}
        · {{ $evaluation->periode->nom }} · Note sur <strong>{{ $evaluation->note_max }}</strong>
    </p>

    @include('structure._messages')

    @if($evaluation->estValidee())
        <div class="alerte">Cette évaluation est validée : les notes sont verrouillées. Le directeur peut la dévalider pour les corriger.</div>
    @endif

    <form method="POST" action="{{ route('evaluations.notes.update', $evaluation) }}">
        @csrf @method('PUT')

        <table>
            <tr><th>Matricule</th><th>Nom et prénoms</th><th style="width:160px">Note / {{ $evaluation->note_max }}</th></tr>
            @forelse($eleves as $eleve)
                @php $valeur = $notes[$eleve->id] ?? null; @endphp
                <tr>
                    <td>{{ $eleve->matricule }}</td>
                    <td>{{ $eleve->nomComplet() }}</td>
                    <td>
                        <input type="number" name="notes[{{ $eleve->id }}]"
                               min="0" max="{{ $evaluation->note_max }}" step="0.25"
                               value="{{ $valeur !== null ? rtrim(rtrim($valeur, '0'), '.') : '' }}"
                               @disabled($evaluation->estValidee())>
                    </td>
                </tr>
            @empty
                <tr><td colspan="3">Aucun élève inscrit dans cette classe.</td></tr>
            @endforelse
        </table>

        <p style="color:var(--discret); font-size:.88rem">Laisse une case vide pour un élève absent ou non noté : il sera ignoré dans le calcul.</p>

        @unless($evaluation->estValidee())
            <button class="btn" type="submit">Enregistrer les notes</button>
        @endunless
    </form>
</div>
@endsection