@extends('layouts.app')
@section('titre', 'Absences et retards')

@section('contenu')
<div class="carte">
    <h2 style="margin-top:0">Absences et retards</h2>
    @include('structure._messages')

    <form method="GET" action="{{ route('absences.index') }}" class="grille" style="margin-bottom:0">
        <div>
            <label>Classe</label>
            <select name="classe_id">
                <option value="">Toutes les classes</option>
                @foreach($classes as $c)
                    <option value="{{ $c->id }}" @selected(request('classe_id') == $c->id)>{{ $c->nom }}</option>
                @endforeach
            </select>
        </div>
        <div><label>Du</label><input type="date" name="du" value="{{ $du }}"></div>
        <div><label>Au</label><input type="date" name="au" value="{{ $au }}"></div>
        <div><button class="btn" type="submit">Filtrer</button></div>
    </form>
</div>

@if($stats)
    <div class="stats">
        <div class="stat"><div class="valeur">{{ $stats['taux'] !== null ? $stats['taux'] . ' %' : '-' }}</div><div class="libelle">Taux de présence</div></div>
        <div class="stat"><div class="valeur">{{ $stats['jours'] }}</div><div class="libelle">Jours d'appel</div></div>
        <div class="stat"><div class="valeur">{{ $stats['absences'] }}</div><div class="libelle">Absences</div></div>
        <div class="stat"><div class="valeur">{{ $stats['retards'] }}</div><div class="libelle">Retards</div></div>
    </div>
@endif

<div class="carte">
    <table>
        <tr><th>Date</th><th>Élève</th><th>Classe</th><th>Type</th><th>Justification</th><th></th></tr>
        @forelse($absences as $a)
            <tr>
                <td>{{ $a->date_absence->format('d/m/Y') }}</td>
                <td>{{ $a->eleve->nomComplet() }}</td>
                <td>{{ $a->classe->nom }}</td>
                <td>
                    {{ \App\Models\Absence::TYPES[$a->type] ?? $a->type }}
                    @if($a->type === 'retard' && $a->duree_minutes) ({{ $a->duree_minutes }} min) @endif
                </td>
                <td>
                    @if($a->justifiee)
                        <span class="badge" style="background:#2E7D32">Justifiée</span>
                        <small style="color:var(--discret)">{{ $a->motif }}</small>
                    @else
                        <span class="badge">Non justifiée</span>
                    @endif
                </td>
                <td>
                    @if(auth()->user()->aLeRole(\App\Enums\Role::Directeur, \App\Enums\Role::Administratif))
                        @if($a->justifiee)
                            <form method="POST" action="{{ route('absences.retirer', $a) }}">
                                @csrf @method('DELETE')
                                <button class="btn btn-noir" type="submit">Retirer</button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('absences.justifier', $a) }}" style="display:flex; gap:6px">
                                @csrf @method('PATCH')
                                <input type="text" name="motif" placeholder="Motif" required style="width:170px">
                                <button class="btn" type="submit">Justifier</button>
                            </form>
                        @endif
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="6">Aucune absence ni aucun retard sur cette période.</td></tr>
        @endforelse
    </table>
</div>
@endsection