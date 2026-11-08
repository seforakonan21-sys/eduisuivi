<?php

namespace App\Http\Controllers\Structure;

use App\Http\Controllers\Concerns\SupprimeEnSecurite;
use App\Http\Controllers\Controller;
use App\Models\Niveau;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NiveauController extends Controller
{
    use SupprimeEnSecurite;

    public function index()
    {
        return view('structure.niveaux', [
            'niveaux' => Niveau::withCount('classes')->orderBy('ordre')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $donnees = $request->validate([
            'nom' => ['required', 'string', 'max:50',
                Rule::unique('niveaux')->where('etablissement_id', $request->user()->etablissement_id)],
            'ordre' => ['required', 'integer', 'between:1,30'],
        ]);

        Niveau::create($donnees);

        return back()->with('succes', 'Niveau ajouté.');
    }

    public function destroy(Niveau $niveau)
    {
        return $this->supprimer($niveau, 'Impossible : ce niveau contient déjà des classes.');
    }
}