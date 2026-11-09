@extends('layouts.app')
@section('titre', 'Décision du conseil de classe')

@section('contenu')
@php $fmt = fn ($v) => $v === null ? '-' : number_format($v, 2, ',', ' '); @endphp
<div class="carte">
    <a href="{{ route('bulletins.index', ['classe_id' => $classe->id, 'periode_id' => $periode->id]) }}">← Retour à la liste</a>
    <h2 style="margin:10px 0 4px">{{ $b['eleve']->nomComplet() }}</h2>
    <p style="margin:0 0 14px; color:var(--discret)">{{ $classe->nom }} · {{ $periode->nom }}</p>

    @include('structure._messages')

    <div class="stats">
        <div class="stat"><div class="valeur">{{ $fmt($b['generale']) }}</div><div class="libelle">Moyenne générale (sur {{ $b['bareme'] }})</div></div>
        <div class="stat"><div class="valeur">{{ $b['rang'] ?? '-' }}</div><div class="libelle">Rang sur {{ $b['effectif'] }}</div></div>
        <div class="stat"><div class="valeur" style="font-size:1.3rem">{{ $b['mention'] ?? '-' }}</div><div class="libelle">Mention automatique</div></div>
    </div>

    <form method="POST" action="{{ route('bulletins.update', [$b['eleve'], $periode]) }}">
        @csrf @method('PUT')

        <label>Appréciation générale</label>
        <textarea name="appreciation_generale" maxlength="500">{{ old('appreciation_generale', $b['appreciation_generale']) }}</textarea>

        <label style="margin-top:14px">Décision du conseil de classe</label>
        <select name="decision">
            <option value="">-</option>
            @foreach(\App\Models\Bulletin::DECISIONS as $decision)
                <option value="{{ $decision }}" @selected(old('decision', $b['decision']) === $decision)>{{ $decision }}</option>
            @endforeach
        </select>

        <div style="margin-top:18px">
            <button class="btn" type="submit">Enregistrer</button>
            <a class="btn btn-noir" target="_blank" href="{{ route('bulletins.pdf', [$b['eleve'], $periode]) }}">Voir le bulletin (PDF)</a>
        </div>
    </form>
</div>
@endsection