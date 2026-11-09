@extends('layouts.app')
@section('titre', 'Bulletins')

@section('contenu')
@php
    $fmt = fn ($v) => $v === null ? '-' : number_format($v, 2, ',', ' ');
    $estDirecteur = auth()->user()->aLeRole(\App\Enums\Role::Directeur);
@endphp

<div class="carte">
    <h2 style="margin-top:0">Bulletins</h2>
    @include('structure._messages')

    <form method="GET" action="{{ route('bulletins.index') }}" class="grille" style="margin-bottom:0">
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
        <div class="alerte" style="margin-top:14px">Aucune classe disponible pour l'année en cours.</div>
    @endif
</div>

@if($classe)
@php $publies = collect($liste)->filter(fn ($b) => $b['bulletin']?->publie)->count(); @endphp
<div class="carte">
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; margin-bottom:14px">
        <div>
            <h3 style="margin:0">{{ $classe->nom }} · {{ $periode->nom }}</h3>
            <small style="color:var(--discret)">{{ $publies }} bulletin(s) publié(s) sur {{ count($liste) }}</small>
        </div>
        <div style="display:flex; gap:8px; flex-wrap:wrap">
            <a class="btn btn-noir" target="_blank" href="{{ route('bulletins.pdf.classe', [$classe, $periode]) }}">Toute la classe (PDF)</a>

            @if($estDirecteur)
                <form method="POST" action="{{ route('bulletins.publier', [$classe, $periode]) }}">
                    @csrf
                    <button class="btn" type="submit">Publier aux parents</button>
                </form>
                @if($publies > 0)
                    <form method="POST" action="{{ route('bulletins.depublier', [$classe, $periode]) }}" onsubmit="return confirm('Retirer la publication de ces bulletins ?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-noir" type="submit">Retirer la publication</button>
                    </form>
                @endif
            @endif
        </div>
    </div>

    <table>
        <tr><th>Rang</th><th>Élève</th><th>Moyenne</th><th>Mention</th><th>Décision</th><th>Parents</th><th></th></tr>
        @forelse($liste as $eleveId => $b)
            <tr>
                <td>{{ $b['rang'] ? $b['rang'] . ($b['rang'] === 1 ? 'er' : 'ème') : '-' }}</td>
                <td>{{ $b['eleve']->nomComplet() }}</td>
                <td><strong>{{ $fmt($b['generale']) }}</strong> / {{ $b['bareme'] }}</td>
                <td>{{ $b['mention'] ?? '-' }}</td>
                <td>{{ $b['decision'] ?? '-' }}</td>
                <td>
                    @if($b['bulletin']?->publie)
                        <span class="badge" style="background:#2E7D32">Publié</span>
                    @else
                        <span class="badge" style="background:#6B7280">Non publié</span>
                    @endif
                </td>
                <td style="white-space:nowrap">
                    <a class="btn btn-noir" target="_blank" href="{{ route('bulletins.pdf', [$b['eleve'], $periode]) }}">PDF</a>
                    @if($estDirecteur)
                        <a class="btn" href="{{ route('bulletins.edit', [$b['eleve'], $periode]) }}">Décision</a>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="7">Aucun élève inscrit dans cette classe.</td></tr>
        @endforelse
    </table>
</div>
@endif
@endsection