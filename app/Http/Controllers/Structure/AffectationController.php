<?php

namespace App\Http\Controllers\Structure;

use App\Enums\Role;
use App\Http\Controllers\Concerns\SupprimeEnSecurite;
use App\Http\Controllers\Controller;
use App\Models\Affectation;
use App\Models\Classe;
use App\Models\Matiere;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AffectationController extends Controller
{
    use SupprimeEnSecurite;

    public function index()
    {
        return view('structure.affectations', [
            'affectations' => Affectation::with(['classe', 'matiere', 'enseignant'])->get()
                ->sortBy(fn ($a) => $a->classe->nom . $a->matiere->nom),
            'classes' => Classe::orderBy('nom')->get(),
            'matieres' => Matiere::orderBy('nom')->get(),
            'enseignants' => User::where('role', Role::Enseignant->value)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $etab = $request->user()->etablissement_id;

        $donnees = $request->validate([
            'classe_id' => ['required', Rule::exists('classes', 'id')->where('etablissement_id', $etab)],
            'matiere_id' => ['required',
                Rule::exists('matieres', 'id')->where('etablissement_id', $etab),
                Rule::unique('affectations')->where('classe_id', $request->classe_id)],
            'enseignant_id' => ['required', Rule::exists('users', 'id')
                ->where('etablissement_id', $etab)
                ->where('role', Role::Enseignant->value)],
            'coefficient' => ['required', 'numeric', 'between:0.5,20'],
        ], [
            'matiere_id.unique' => 'Cette matière est déjà affectée à cette classe.',
        ]);

        Affectation::create($donnees);

        return back()->with('succes', 'Affectation enregistrée.');
    }

    public function destroy(Affectation $affectation)
    {
        return $this->supprimer($affectation, 'Impossible de supprimer cette affectation.');
    }
}