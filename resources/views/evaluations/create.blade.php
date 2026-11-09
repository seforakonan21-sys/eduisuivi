@extends('layouts.app')
@section('titre', 'Nouvelle évaluation')

@section('contenu')
<div class="carte">
    <h2 style="margin-top:0">Nouvelle évaluation</h2>
    @include('structure._messages')

    @if(! $annee)
        <div class="alerte">Aucune année scolaire active : l'évaluation ne peut pas être créée.</div>
    @elseif($affectations->isEmpty())
        <div class="alerte">Aucune affectation trouvée pour l'année {{ $annee->libelle }}. Une classe, une matière et un enseignant doivent d'abord être affectés ensemble.</div>
    @else
        <form method="POST" action="{{ route('evaluations.store') }}" class="grille">
            @csrf
            <div>
                <label>Classe et matière</label>
                <select name="affectation_id" id="affectation_id" required>
                    @foreach($affectations as $a)
                        <option value="{{ $a->id }}" data-bareme="{{ $a->classe->niveau->bareme }}" @selected(old('affectation_id') == $a->id)>
                            {{ $a->classe->nom }} · {{ $a->matiere->nom }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label>Période</label>
                <select name="periode_id" required>
                    @foreach($periodes as $p)
                        <option value="{{ $p->id }}" @selected(old('periode_id') == $p->id)>{{ $p->nom }}</option>
                    @endforeach
                </select>
            </div>
            <div><label>Titre</label><input type="text" name="titre" placeholder="Devoir 1" value="{{ old('titre') }}" required></div>
            <div>
                <label>Type</label>
                <select name="type" required>
                    @foreach(\App\Models\Evaluation::TYPES as $valeur => $libelle)
                        <option value="{{ $valeur }}" @selected(old('type') === $valeur)>{{ $libelle }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label>Note sur</label>
                <select name="note_max" id="note_max" data-ancien="{{ old('note_max') }}" required>
                    <option value="10" @selected(old('note_max') == 10)>10</option>
                    <option value="20" @selected(old('note_max') == 20)>20</option>
                </select>
            </div>
            <div><label>Date</label><input type="date" name="date_evaluation" value="{{ old('date_evaluation', now()->toDateString()) }}" required></div>
            <div><button class="btn" type="submit">Créer l'évaluation</button></div>
        </form>

        <script>
            // Propose automatiquement le barème du niveau de la classe choisie
            const choix = document.getElementById('affectation_id');
            const maximum = document.getElementById('note_max');

            function appliquerBareme() {
                const bareme = choix.options[choix.selectedIndex]?.dataset.bareme;
                if (bareme) {
                    maximum.value = bareme;
                }
            }

            choix.addEventListener('change', appliquerBareme);

            // Au chargement, on garde le choix de l'utilisateur si le formulaire revient avec des erreurs
            if (! maximum.dataset.ancien) {
                appliquerBareme();
            }
        </script>
    @endif
</div>
@endsection