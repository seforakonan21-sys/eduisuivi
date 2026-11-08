<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Eleve;
use App\Models\User;
use App\Services\InscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class EleveController extends Controller
{
    public function index(Request $request)
    {
        $eleves = Eleve::with('inscriptions.classe')
            ->when($request->q, function ($requete, $terme) {
                $requete->where(function ($w) use ($terme) {
                    $w->where('nom', 'like', "%{$terme}%")
                      ->orWhere('prenoms', 'like', "%{$terme}%")
                      ->orWhere('matricule', 'like', "%{$terme}%");
                });
            })
            ->orderBy('nom')->orderBy('prenoms')
            ->paginate(20)
            ->withQueryString();

        return view('eleves.index', compact('eleves'));
    }

    public function create()
    {
        $annee = AnneeScolaire::where('est_active', true)->first();

        return view('eleves.create', [
            'annee' => $annee,
            'classes' => $annee?->classes()->orderBy('nom')->get() ?? collect(),
        ]);
    }

    public function store(Request $request, InscriptionService $inscriptions)
    {
        $etab = $request->user()->etablissement;
        $annee = AnneeScolaire::where('est_active', true)->first();

        if (! $annee) {
            return back()->withInput()->withErrors([
                'classe_id' => "Aucune année scolaire active : créez-en une avant d'inscrire des élèves.",
            ]);
        }

        $donnees = $request->validate([
            'nom' => ['required', 'string', 'max:100'],
            'prenoms' => ['required', 'string', 'max:150'],
            'date_naissance' => ['required', 'date', 'before:today'],
            'sexe' => ['required', Rule::in(['M', 'F'])],
            'telephone' => ['nullable', 'string', 'max:20'],
            'adresse' => ['nullable', 'string', 'max:255'],
            'photo' => ['nullable', 'image', 'max:2048'],
            'classe_id' => ['required', Rule::exists('classes', 'id')
                ->where('etablissement_id', $etab->id)
                ->where('annee_scolaire_id', $annee->id)],
        ]);

        $eleve = DB::transaction(function () use ($donnees, $etab, $inscriptions) {
            $eleve = Eleve::create([
                ...Arr::except($donnees, ['classe_id', 'photo']),
                'matricule' => Eleve::genererMatricule($etab),
            ]);

            $inscriptions->inscrire($eleve, Classe::findOrFail($donnees['classe_id']));

            return $eleve;
        });

        // La photo n'est enregistrée qu'une fois l'inscription réussie
        if ($request->hasFile('photo')) {
            $eleve->update(['photo' => $request->file('photo')->store('eleves', 'public')]);
        }

        return redirect()->route('eleves.show', $eleve)->with('succes', 'Élève enregistré et inscrit.');
    }

    public function show(Eleve $eleve)
    {
        $eleve->load(['inscriptions.classe', 'inscriptions.anneeScolaire', 'parents']);
        $annee = AnneeScolaire::where('est_active', true)->first();

        return view('eleves.show', [
            'eleve' => $eleve,
            'annee' => $annee,
            'classes' => $annee?->classes()->orderBy('nom')->get() ?? collect(),
            'parentsDisponibles' => User::where('role', Role::ParentEleve->value)
                ->whereNotIn('id', $eleve->parents->pluck('id'))
                ->orderBy('name')->get(),
        ]);
    }

    public function destroy(Eleve $eleve)
    {
        if ($eleve->photo) {
            Storage::disk('public')->delete($eleve->photo);
        }
        $eleve->delete();

        return redirect()->route('eleves.index')->with('succes', 'Dossier élève supprimé.');
    }
}