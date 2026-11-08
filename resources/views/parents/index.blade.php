@extends('layouts.app')
@section('titre', 'Parents')

@section('contenu')
@include('structure._nav')
<div class="carte">
    <h2 style="margin-top:0">Comptes parents</h2>
    @include('structure._messages')

    @if(session('mot_de_passe_temporaire'))
        <div class="alerte">
            <strong>Compte créé : {{ session('parent_cree') }}</strong><br>
            Mot de passe temporaire : <code style="font-size:1.15rem">{{ session('mot_de_passe_temporaire') }}</code><br>
            Notez-le et transmettez-le au parent maintenant : il ne sera plus affiché.
        </div>
    @endif

    <form method="POST" action="{{ route('parents.store') }}" class="grille">
        @csrf
        <div><label>Nom complet</label><input type="text" name="name" value="{{ old('name') }}" required></div>
        <div><label>E-mail</label><input type="email" name="email" value="{{ old('email') }}" required></div>
        <div><label>Téléphone</label><input type="text" name="telephone" value="{{ old('telephone') }}"></div>
        <div><button class="btn" type="submit">Créer le compte</button></div>
    </form>

    <table>
        <tr><th>Nom</th><th>E-mail</th><th>Téléphone</th><th>Enfants</th><th></th></tr>
        @forelse($parents as $p)
            <tr>
                <td>{{ $p->name }}</td>
                <td>{{ $p->email }}</td>
                <td>{{ $p->telephone ?? '-' }}</td>
                <td>{{ $p->enfants_count }}</td>
                <td>
                    <form method="POST" action="{{ route('parents.destroy', $p) }}" onsubmit="return confirm('Supprimer ce compte parent ?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-noir" type="submit">Supprimer</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="5">Aucun compte parent pour le moment.</td></tr>
        @endforelse
    </table>
</div>
@endsection