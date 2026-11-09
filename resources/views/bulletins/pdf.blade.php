<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Bulletin scolaire</title>
    <style>
        @page { margin: 26px 30px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111; }
        table { width: 100%; border-collapse: collapse; }
        .titre { text-align: center; font-size: 15px; font-weight: bold; margin: 10px 0 2px; }
        .sous-titre { text-align: center; margin-bottom: 10px; }
        .cadre { border: 1px solid #111; }
        .cadre td { padding: 6px 8px; vertical-align: top; }
        .notes th { background: #111; color: #fff; padding: 5px; border: 1px solid #111; font-size: 9px; }
        .notes td { border: 1px solid #888; padding: 5px; }
        .centre { text-align: center; }
        .gras { font-weight: bold; }
        .rouge { color: #C8102E; }
        .petit { font-size: 8.5px; color: #444; }
        .saut { page-break-after: always; }
    </style>
</head>
<body>
@php $fmt = fn ($v) => $v === null ? '-' : number_format($v, 2, ',', ' '); @endphp

@foreach($bulletins as $b)
@php
    $eleve = $b['eleve'];
    $etab = $b['etablissement'];
    $bareme = $b['bareme'];
    $echec = $b['generale'] !== null && $b['generale'] < $bareme / 2;
@endphp
<div class="{{ $loop->last ? '' : 'saut' }}">

    <table>
        <tr>
            <td style="width:36%; vertical-align:top;">
                <div class="gras">MINISTÈRE DE L'ÉDUCATION NATIONALE</div>
                <div class="gras rouge" style="margin-top:6px;">{{ mb_strtoupper($etab->nom) }}</div>
                @if($etab->adresse)<div class="petit">{{ $etab->adresse }}</div>@endif
                @if($etab->telephone)<div class="petit">Tél. : {{ $etab->telephone }}</div>@endif
            </td>
            <td style="width:28%; text-align:center; vertical-align:top;">
                @if($b['logo'])<img src="{{ $b['logo'] }}" style="height:62px;">@endif
            </td>
            <td style="width:36%; text-align:right; vertical-align:top;">
                <div class="gras">RÉPUBLIQUE DE CÔTE D'IVOIRE</div>
                <div class="petit">Union – Discipline – Travail</div>
            </td>
        </tr>
    </table>

    <div class="titre">BULLETIN DE NOTES</div>
    <div class="sous-titre">{{ $b['periode']->nom }} · Année scolaire {{ $b['annee']->libelle }}</div>

    <table class="cadre" style="margin-bottom:8px;">
        <tr>
            <td style="width:50%;"><span class="petit">Nom et prénoms</span><br><span class="gras">{{ $eleve->nomComplet() }}</span></td>
            <td style="width:25%;"><span class="petit">Matricule</span><br><span class="gras">{{ $eleve->matricule }}</span></td>
            <td style="width:25%;"><span class="petit">Classe</span><br><span class="gras">{{ $b['classe']->nom }}</span> · effectif {{ $b['effectif'] }}</td>
        </tr>
        <tr>
            <td><span class="petit">Date de naissance</span><br>{{ $eleve->date_naissance->format('d/m/Y') }}</td>
            <td><span class="petit">Sexe</span><br>{{ $eleve->sexe === 'F' ? 'Féminin' : 'Masculin' }}</td>
            <td><span class="petit">Notes sur</span><br>{{ $bareme }}</td>
        </tr>
    </table>

    <table class="notes">
        <tr>
            <th style="text-align:left;">Matière</th>
            <th>Coef.</th>
            <th>Moy. élève</th>
            <th>Moy. classe</th>
            <th style="text-align:left;">Appréciation</th>
        </tr>
        @foreach($b['matieres'] as $m)
            <tr>
                <td>{{ $m['nom'] }}</td>
                <td class="centre">{{ rtrim(rtrim(number_format($m['coefficient'], 2, ',', ''), '0'), ',') }}</td>
                <td class="centre gras">{{ $fmt($m['moyenne']) }}</td>
                <td class="centre">{{ $fmt($m['moyenne_classe']) }}</td>
                <td>{{ $m['appreciation'] }}</td>
            </tr>
        @endforeach
        <tr>
            <td class="gras" colspan="2">MOYENNE GÉNÉRALE (sur {{ $bareme }})</td>
            <td class="centre gras {{ $echec ? 'rouge' : '' }}" style="font-size:12px;">{{ $fmt($b['generale']) }}</td>
            <td class="centre gras">{{ $fmt($b['moyenne_classe']) }}</td>
            <td>
                Rang : <span class="gras">{{ $b['rang'] ? $b['rang'] . ($b['rang'] === 1 ? 'er' : 'ème') . ' / ' . $b['effectif'] : '-' }}</span>
                · Mention : <span class="gras">{{ $b['mention'] ?? '-' }}</span>
            </td>
        </tr>
    </table>

    <p class="petit" style="margin:4px 0 0;">
        Moyenne la plus forte : {{ $fmt($b['meilleure']) }} · Moyenne la plus faible : {{ $fmt($b['plus_faible']) }}
    </p>

    <table class="cadre" style="margin-top:8px;">
        <tr>
            <td>
                <span class="gras">Assiduité :</span>
                {{ $b['absences']['absences'] }} absence(s), dont {{ $b['absences']['justifiees'] }} justifiée(s)
                · {{ $b['absences']['retards'] }} retard(s)
                @if($b['absences']['minutes_retard'] > 0) ({{ $b['absences']['minutes_retard'] }} min) @endif
            </td>
        </tr>
    </table>

    <table class="cadre" style="margin-top:8px;">
        <tr>
            <td style="width:60%;">
                <span class="gras">Appréciation générale</span><br>
                {{ $b['appreciation_generale'] }}<br><br>
            </td>
            <td>
                <span class="gras">Décision du conseil de classe</span><br>
                {{ $b['decision'] }}
            </td>
        </tr>
    </table>

    <table style="margin-top:14px;">
        <tr>
            <td class="centre" style="width:50%;">
                <span class="gras">Le parent / tuteur</span><br><br><br><br>
            </td>
            <td class="centre" style="width:50%;">
                <span class="gras">Le Directeur</span><br>
                @if($b['cachet'])
                    <img src="{{ $b['cachet'] }}" style="height:70px;"><br>
                @else
                    <br><br><br>
                @endif
                {{ $etab->signataire_nom }}
            </td>
        </tr>
    </table>

    <div class="petit centre" style="margin-top:10px;">Bulletin généré par EduSuivi le {{ now()->format('d/m/Y') }}</div>
</div>
@endforeach
</body>
</html>