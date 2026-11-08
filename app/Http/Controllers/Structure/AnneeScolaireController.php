<?php

namespace App\Http\Controllers\Structure;

use App\Http\Controllers\Concerns\SupprimeEnSecurite;
use App\Http\Controllers\Controller;
use App\Models\AnneeScolaire;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AnneeScolaireController extends Controller
{
    use SupprimeEnSecurite;

    public function index()
    {
        return view('structure.annees', [
            'annees' => AnneeScolaire::orderByDesc('date_debut')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $etab = $request->user()->etablissement_id;

        $donnees = $request->validate([
            'libelle' => ['required', 'string', 'max:20',
                Rule::unique('annees_scolaires')->where('etablissement_id', $etab)],
            'date_debut' => ['required', 'date'],
            'date_fin' => ['required', 'date', 'after:date_debut'],
        ]);
        $donnees['est_active'] = $request->boolean('est_active');

        $annee = AnneeScolaire::create($donnees);

        // Une seule année active par établissement
        if ($annee->est_active) {
            AnneeScolaire::where('id', '!=', $annee->id)->update(['est_active' => false]);
        }

        return back()->with('succes', 'Année scolaire ajoutée.');
    }

    public function destroy(AnneeScolaire $annee)
    {
        return $this->supprimer($annee, 'Impossible : cette année contient déjà des classes.');
    }
}