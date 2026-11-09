@extends('layouts.app')
@section('titre', 'Absences et retards de mes enfants')

@section('contenu')
@forelse($enfants as $enfant)
    @php
        $liste = $absences->get($enfant->id, collect());
        $classe = $enfant->inscriptions->first()?->classe;
    @endphp
    <div class="carte">
        <h3 style="margin-top:0">
            {{ $enfant->nomComplet() }}
            @if($classe)<small style="color:var(--discret)">· {{ $classe->nom }}</small>@endif
        </h3>
        <p style="color:var(--discret); margin-top:0">
            90 derniers jours : {{ $liste->where('type', 'absence')->count() }} absence(s),
            {{ $liste->where('type', 'retard')->count() }} retard(s)
        </p>

        <table>
            <tr><th>Date</th><th>Type</th><th>Justification</th></tr>
            @forelse($liste as $a)
                <tr>
                    <td>{{ $a->date_absence->format('d/m/Y') }}</td>
                    <td>
                        {{ \App\Models\Absence::TYPES[$a->type] ?? $a->type }}
                        @if($a->type === 'retard' && $a->duree_minutes) ({{ $a->duree_minutes }} min) @endif
                    </td>
                    <td>
                        @if($a->justifiee)
                            <span class="badge" style="background:#2E7D32">Justifiée</span> {{ $a->motif }}
                        @else
                            <span class="badge">Non justifiée</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="3">Aucune absence ni aucun retard. Bravo !</td></tr>
            @endforelse
        </table>
    </div>
@empty
    <div class="carte">Aucun enfant n'est rattaché à votre compte. Contactez l'établissement.</div>
@endforelse
@endsection