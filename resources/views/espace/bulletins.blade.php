@extends('layouts.app')
@section('titre', 'Bulletins de mes enfants')

@section('contenu')
@forelse($enfants as $enfant)
    <div class="carte">
        <h3 style="margin-top:0">{{ $enfant->nomComplet() }}</h3>

        <table>
            <tr><th>Période</th><th></th></tr>
            @forelse($bulletins->get($enfant->id, collect()) as $b)
                <tr>
                    <td>{{ $b->periode->nom }}</td>
                    <td><a class="btn" target="_blank" href="{{ route('bulletins.pdf', [$enfant, $b->periode]) }}">Télécharger le bulletin (PDF)</a></td>
                </tr>
            @empty
                <tr><td colspan="2">Aucun bulletin publié pour le moment.</td></tr>
            @endforelse
        </table>
    </div>
@empty
    <div class="carte">Aucun enfant n'est rattaché à votre compte. Contactez l'établissement.</div>
@endforelse
@endsection