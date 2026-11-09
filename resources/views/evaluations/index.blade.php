@extends('layouts.app')
@section('titre', 'Évaluations')

@section('contenu')
<div class="carte">
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; margin-bottom:14px">
        <h2 style="margin:0">Évaluations</h2>
        <a class="btn" href="{{ route('evaluations.create') }}">Nouvelle évaluation</a>
    </div>

    @include('structure._messages')

    <table>
        <tr>
            <th>Date</th><th>Titre</th><th>Classe</th><th>Matière</th><th>Période</th>
            <th>Barème</th><th>Notes</th><th>Statut</th><th></th>
        </tr>
        @forelse($evaluations as $e)
            <tr>
                <td>{{ $e->date_evaluation->format('d/m/Y') }}</td>
                <td>{{ $e->titre }}<br><small style="color:var(--discret)">{{ \App\Models\Evaluation::TYPES[$e->type] ?? $e->type }}</small></td>
                <td>{{ $e->affectation->classe->nom }}</td>
                <td>{{ $e->affectation->matiere->nom }}</td>
                <td>{{ $e->periode->nom }}</td>
                <td>Sur {{ $e->note_max }}</td>
                <td>{{ $e->notes_count }}</td>
                <td>
                    @if($e->estValidee())
                        <span class="badge">Validé</span>
                    @else
                        <span class="badge" style="background:#6B7280">Brouillon</span>
                    @endif
                </td>
                <td style="white-space:nowrap">
                    <a class="btn btn-noir" href="{{ route('evaluations.notes.edit', $e) }}">
                        {{ $e->estValidee() ? 'Voir les notes' : 'Saisir les notes' }}
                    </a>

                    @if(auth()->user()->aLeRole(\App\Enums\Role::Directeur))
                        <form method="POST" action="{{ route($e->estValidee() ? 'evaluations.devalider' : 'evaluations.valider', $e) }}" style="display:inline">
                            @csrf @method('PATCH')
                            <button class="btn" type="submit">{{ $e->estValidee() ? 'Dévalider' : 'Valider' }}</button>
                        </form>
                    @endif

                    @unless($e->estValidee())
                        <form method="POST" action="{{ route('evaluations.destroy', $e) }}" style="display:inline" onsubmit="return confirm('Supprimer cette évaluation et ses notes ?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-noir" type="submit">Supprimer</button>
                        </form>
                    @endunless
                </td>
            </tr>
        @empty
            <tr><td colspan="9">Aucune évaluation pour le moment.</td></tr>
        @endforelse
    </table>
</div>
@endsection