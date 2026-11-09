@extends('layouts.app')
@section('titre', 'Résultats')

@section('contenu')
@php
    $fmt = fn ($v) => $v === null ? '-' : number_format($v, 2, ',', ' ');
@endphp

<div class="carte">
    <h2 style="margin-top:0">Résultats par classe</h2>

    <form method="GET" action="{{ route('resultats.index') }}" class="grille">
        <div>
            <label>Classe</label>
            <select name="classe_id" required>
                @foreach($classes as $c)
                    <option value="{{ $c->id }}" @selected(request('classe_id') == $c->id)>{{ $c->nom }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label>Période</label>
            <select name="periode_id" required>
                @foreach($periodes as $p)
                    <option value="{{ $p->id }}" @selected(request('periode_id') == $p->id)>{{ $p->nom }}</option>
                @endforeach
            </select>
        </div>
        <div><button class="btn" type="submit">Afficher</button></div>
    </form>

    @if($classes->isEmpty())
        <div class="alerte">Aucune classe disponible pour l'année en cours.</div>
    @endif
</div>

@if($donnees)
    <div class="stats">
        <div class="stat"><div class="valeur">{{ $stats['effectif'] }}</div><div class="libelle">Élèves</div></div>
        <div class="stat"><div class="valeur">{{ $fmt($stats['moyenne_classe']) }}</div><div class="libelle">Moyenne de la classe (sur {{ $donnees['bareme'] }})</div></div>
        <div class="stat"><div class="valeur">{{ $stats['taux_reussite'] !== null ? $stats['taux_reussite'] . ' %' : '-' }}</div><div class="libelle">Taux de réussite (≥ {{ $donnees['bareme'] / 2 }})</div></div>
        <div class="stat"><div class="valeur">{{ $fmt($stats['meilleure']) }}</div><div class="libelle">Meilleure moyenne</div></div>
    </div>

    <div class="carte">
        <h3 style="margin-top:0">{{ $classe->nom }} · {{ $periode->nom }} <small style="color:var(--discret)">(notes sur {{ $donnees['bareme'] }})</small></h3>
        <p style="color:var(--discret); font-size:.88rem; margin-top:0">Seules les évaluations validées par le directeur sont comptées.</p>

        <table>
            <tr>
                <th>Rang</th>
                <th>Élève</th>
                @foreach($donnees['affectations'] as $aff)
                    <th>{{ $aff->matiere->nom }}<br>(coef {{ rtrim(rtrim(number_format($aff->coefficient, 2, '.', ''), '0'), '.') }})</th>
                @endforeach
                <th>Moyenne générale</th>
            </tr>
            @forelse($donnees['resultats'] as $r)
                @php $echec = $r['generale'] !== null && $r['generale'] < $donnees['bareme'] / 2; @endphp
                <tr>
                    <td>{{ $r['rang'] ? $r['rang'] . ($r['rang'] === 1 ? 'er' : 'ème') : '-' }}</td>
                    <td>{{ $r['eleve']->nomComplet() }}</td>
                    @foreach($donnees['affectations'] as $aff)
                        <td>{{ $fmt($r['moyennes'][$aff->id] ?? null) }}</td>
                    @endforeach
                    <td @style(['font-weight:700', 'color:var(--rouge)' => $echec])>{{ $fmt($r['generale']) }}</td>
                </tr>
            @empty
                <tr><td colspan="{{ 3 + count($donnees['affectations']) }}">Aucun élève inscrit dans cette classe.</td></tr>
            @endforelse
        </table>
    </div>
@endif
@endsection