<?php

namespace App\Http\Controllers;

use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Eleve;
use App\Models\Inscription;
use App\Services\InscriptionService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InscriptionController extends Controller
{
    public function store(Request $request, Eleve $eleve, InscriptionService $service)
    {
        $annee = AnneeScolaire::where('est_active', true)->first();

        if (! $annee) {
            return back()->withErrors(['classe_id' => 'Aucune année scolaire active.']);
        }

        $donnees = $request->validate([
            'classe_id' => ['required', Rule::exists('classes', 'id')
                ->where('etablissement_id', $request->user()->etablissement_id)
                ->where('annee_scolaire_id', $annee->id)],
        ]);

        $service->inscrire($eleve, Classe::findOrFail($donnees['classe_id']));

        return back()->with('succes', 'Inscription enregistrée.');
    }

    public function update(Request $request, Inscription $inscription)
    {
        $donnees = $request->validate([
            'statut' => ['required', Rule::in(array_keys(Inscription::STATUTS))],
        ]);

        $inscription->update($donnees);

        return back()->with('succes', "Statut d'inscription mis à jour.");
    }
}