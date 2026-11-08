<?php

namespace App\Http\Controllers\Structure;

use App\Http\Controllers\Concerns\SupprimeEnSecurite;
use App\Http\Controllers\Controller;
use App\Models\AnneeScolaire;
use App\Models\Periode;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PeriodeController extends Controller
{
    use SupprimeEnSecurite;

    public function index()
    {
        return view('structure.periodes', [
            'annees' => AnneeScolaire::with('periodes')->orderByDesc('date_debut')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $etab = $request->user()->etablissement_id;

        $donnees = $request->validate([
            'annee_scolaire_id' => ['required',
                Rule::exists('annees_scolaires', 'id')->where('etablissement_id', $etab)],
            'nom' => ['required', 'string', 'max:50'],
            'ordre' => ['required', 'integer', 'between:1,6',
                Rule::unique('periodes')->where('annee_scolaire_id', $request->annee_scolaire_id)],
            'date_debut' => ['required', 'date'],
            'date_fin' => ['required', 'date', 'after:date_debut'],
        ]);

        Periode::create($donnees);

        return back()->with('succes', 'Période ajoutée.');
    }

    public function destroy(Periode $periode)
    {
        return $this->supprimer($periode, 'Impossible de supprimer cette période.');
    }
}