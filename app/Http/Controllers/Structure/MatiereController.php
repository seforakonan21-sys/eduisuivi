<?php

namespace App\Http\Controllers\Structure;

use App\Http\Controllers\Concerns\SupprimeEnSecurite;
use App\Http\Controllers\Controller;
use App\Models\Matiere;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MatiereController extends Controller
{
    use SupprimeEnSecurite;

    public function index()
    {
        return view('structure.matieres', ['matieres' => Matiere::orderBy('nom')->get()]);
    }

    public function store(Request $request)
    {
        $donnees = $request->validate([
            'nom' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:10',
                Rule::unique('matieres')->where('etablissement_id', $request->user()->etablissement_id)],
        ]);
        $donnees['code'] = strtoupper($donnees['code']);

        Matiere::create($donnees);

        return back()->with('succes', 'Matière ajoutée.');
    }

    public function destroy(Matiere $matiere)
    {
        return $this->supprimer($matiere, 'Impossible : cette matière est déjà affectée à une classe.');
    }
}