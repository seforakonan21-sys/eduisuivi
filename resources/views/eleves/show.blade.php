@extends('layouts.app')
@section('titre', $eleve->nomComplet())

@section('contenu')
@include('structure._nav')
@include('structure._messages')

<div class="carte" style="margin-bottom:16px">
    <div style="display:flex; gap:20px; align-items:center; flex-wrap:wrap">
        @if($eleve->photo)
            <img class="photo" src="{{ asset('storage/' . $eleve->photo) }}" alt="Photo de {{ $eleve->nomComplet() }}">
        @else
            <div class="photo"></div>
        @endif
        <div>
            <h2 style="margin:0">{{ $eleve->nomComplet() }}</h2>
            <p style="margin:6px 0">Matricule : <strong>{{ $eleve->matricule }}</strong></p>
            <p style="margin:0">
                Né(e) le {{ $eleve->date_naissance->format('d/m/Y') }}
                · {{ $eleve->sexe === 'F' ? 'Féminin' : 'Masculin' }}
            </p>
            @if($eleve->telephone)<p style="margin:6px 0 0">Téléphone : {{ $eleve->telephone }}</p>@endif
            @if($eleve->adresse)<p style="margin:6px 0 0">Adresse : {{ $eleve->adresse }}</p>@endif
        </div>
    </div>
</div>

<div class="carte" style="margin-bottom:16px">
    <h3 style="margin-top:0">Historique des inscriptions</h3>
    <table>
        <tr><th>Année</th><th>Classe</th><th>Date</th><th>Statut</th></tr>
        @forelse($eleve->inscriptions as $i)
            <tr>
                <td>{{ $i->anneeScolaire->libelle }}</td>
                <td>{{ $i->classe->nom }}</td>
                <td>{{ $i->date_inscription->format('d/m/Y') }}</td>
                <td>
                    <form method="POST" action="{{ route('inscriptions.update', $i) }}" style="display:flex; gap:8px">
                        @csrf @method('PATCH')
                        <select name="statut">
                            @foreach(\App\Models\Inscription::STATUTS as $valeur => $libelle)
                                <option value="{{ $valeur }}" @selected($i->statut === $valeur)>{{ $libelle }}</option>
                            @endforeach
                        </select>
                        <button class="btn btn-noir" type="submit">OK</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="4">Aucune inscription.</td></tr>
        @endforelse
    </table>

    @if($annee)
        <form method="POST" action="{{ route('eleves.inscriptions.store', $eleve) }}" class="grille" style="margin-top:16px">
            @csrf
            <div>
                <label>Inscrire pour {{ $annee->libelle }}</label>
                <select name="classe_id" required>
                    @foreach($classes as $c)
                        <option value="{{ $c->id }}">{{ $c->nom }}</option>
                    @endforeach
                </select>
            </div>
            <div><button class="btn" type="submit">Inscrire</button></div>
        </form>
    @endif
</div>

<div class="carte" style="margin-bottom:16px">
    <h3 style="margin-top:0">Parents et tuteurs</h3>
    <table>
        <tr><th>Nom</th><th>Lien</th><th>E-mail</th><th>Téléphone</th><th></th></tr>
        @forelse($eleve->parents as $p)
            <tr>
                <td>{{ $p->name }}</td>
                <td>{{ \App\Models\Eleve::LIENS[$p->pivot->lien] ?? $p->pivot->lien }}</td>
                <td>{{ $p->email }}</td>
                <td>{{ $p->telephone ?? '-' }}</td>
                <td>
                    <form method="POST" action="{{ route('eleves.parents.destroy', [$eleve, $p]) }}" onsubmit="return confirm('Retirer ce lien ?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-noir" type="submit">Retirer</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="5">Aucun parent lié pour le moment.</td></tr>
        @endforelse
    </table>

    @if($parentsDisponibles->isNotEmpty())
        <form method="POST" action="{{ route('eleves.parents.store', $eleve) }}" class="grille" style="margin-top:16px">
            @csrf
            <div>
                <label>Parent</label>
                <select name="parent_id" required>
                    @foreach($parentsDisponibles as $p)
                        <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->email }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label>Lien</label>
                <select name="lien" required>
                    @foreach(\App\Models\Eleve::LIENS as $valeur => $libelle)
                        <option value="{{ $valeur }}">{{ $libelle }}</option>
                    @endforeach
                </select>
            </div>
            <div><button class="btn" type="submit">Lier</button></div>
        </form>
    @else
        <p style="margin-top:14px">Aucun autre parent disponible. <a href="{{ route('parents.index') }}">Créer un compte parent</a>.</p>
    @endif
</div>

<form method="POST" action="{{ route('eleves.destroy', $eleve) }}" onsubmit="return confirm('Supprimer définitivement ce dossier élève ?')">
    @csrf @method('DELETE')
    <button class="btn btn-noir" type="submit">Supprimer ce dossier</button>
</form>
@endsection