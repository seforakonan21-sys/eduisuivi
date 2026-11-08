<?php

namespace App\Http\Controllers\Structure;

use App\Http\Controllers\Concerns\SupprimeEnSecurite;
use App\Http\Controllers\Controller;
use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Niveau;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClasseController extends Controller
{
    use SupprimeEnSecurite;

    public function index()
    {
        return view('structure.classes', [
            'classes' => Classe::with(['niveau', 'anneeScolaire'])->orderBy('nom')->get(),
            'niveaux' => Niveau::orderBy('ordre')->get(),
            'annees' => AnneeScolaire::orderByDesc('date_debut')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $etab = $request->user()->etablissement_id;

        $donnees = $request->validate([
            'niveau_id' => ['required', Rule::exists('niveaux', 'id')->where('etablissement_id', $etab)],
            'annee_scolaire_id' => ['required',
                Rule::exists('annees_scolaires', 'id')->where('etablissement_id', $etab)],
            'nom' => ['required', 'string', 'max:50',
                Rule::unique('classes')->where('annee_scolaire_id', $request->annee_scolaire_id)],
            'effectif_max' => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);

        Classe::create($donnees);

        return back()->with('succes', 'Classe ajoutée.');
    }

    public function destroy(Classe $classe)
    {
        return $this->supprimer($classe, 'Impossible de supprimer cette classe.');
    }
}