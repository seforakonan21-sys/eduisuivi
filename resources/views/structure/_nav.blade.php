@php
    $liens = [
        'structure.annees.index' => ['Années', 'structure.annees.*'],
        'structure.periodes.index' => ['Périodes', 'structure.periodes.*'],
        'structure.niveaux.index' => ['Niveaux', 'structure.niveaux.*'],
        'structure.classes.index' => ['Classes', 'structure.classes.*'],
        'structure.matieres.index' => ['Matières', 'structure.matieres.*'],
        'structure.affectations.index' => ['Affectations', 'structure.affectations.*'],
        'eleves.index' => ['Élèves', 'eleves.*'],
        'parents.index' => ['Parents', 'parents.*'],
    ];
@endphp
<nav class="menu">
    <a href="{{ route('dashboard') }}">← Accueil</a>
    @foreach($liens as $route => [$texte, $motif])
        <a href="{{ route($route) }}" class="{{ request()->routeIs($motif) ? 'actif' : '' }}">{{ $texte }}</a>
    @endforeach
</nav>