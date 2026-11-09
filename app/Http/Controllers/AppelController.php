<?php

namespace App\Http\Controllers;

use App\Models\Absence;
use App\Models\AnneeScolaire;
use App\Models\Appel;
use App\Models\Classe;
use App\Models\Eleve;
use App\Models\Inscription;
use App\Notifications\AbsenceEnregistree;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;

class AppelController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'classe_id' => ['nullable', 'integer'],
            'date' => ['nullable', 'date'],
        ]);

        $user = $request->user();
        $annee = AnneeScolaire::where('est_active', true)->first();

        $classes = $annee
            ? Classe::accessiblePar($user)->where('annee_scolaire_id', $annee->id)->orderBy('nom')->get()
            : collect();

        $date = Carbon::parse($request->input('date', now()->toDateString()))->toDateString();
        $classe = null;
        $eleves = collect();
        $existantes = collect();
        $appelFait = false;

        if ($request->filled('classe_id')) {
            $classe = Classe::accessiblePar($user)->findOrFail($request->classe_id);
            $eleves = $this->elevesInscrits($classe);
            $existantes = Absence::whereIn('eleve_id', $eleves->pluck('id'))
                ->whereDate('date_absence', $date)->get()->keyBy('eleve_id');
            $appelFait = Appel::where('classe_id', $classe->id)->whereDate('date_appel', $date)->exists();
        }

        return view('appel.index', compact('classes', 'classe', 'date', 'eleves', 'existantes', 'appelFait'));
    }

    public function store(Request $request)
    {
        $donnees = $request->validate([
            'classe_id' => ['required', 'integer'],
            'date' => ['required', 'date', 'before_or_equal:today'],
            'statuts' => ['required', 'array'],
            'statuts.*' => ['required', Rule::in(['present', 'absence', 'retard'])],
            'minutes.*' => ['nullable', 'integer', 'between:1,240'],
        ]);

        $user = $request->user();
        $classe = Classe::accessiblePar($user)->findOrFail($donnees['classe_id']);
        $date = Carbon::parse($donnees['date'])->toDateString();
        $eleves = $this->elevesInscrits($classe);
        $nouvelles = [];

        DB::transaction(function () use ($request, $classe, $date, $eleves, $user, &$nouvelles) {
            $appel = Appel::where('classe_id', $classe->id)->whereDate('date_appel', $date)->first()
                ?? new Appel(['classe_id' => $classe->id, 'date_appel' => $date]);
            $appel->fill(['etablissement_id' => $classe->etablissement_id, 'saisi_par' => $user->id])->save();

            foreach ($eleves as $eleve) {
                $statut = $request->input("statuts.{$eleve->id}", 'present');
                $existante = Absence::where('eleve_id', $eleve->id)->whereDate('date_absence', $date)->first();

                if ($statut === 'present') {
                    $existante?->delete();
                    continue;
                }

                $absence = $existante ?? new Absence(['eleve_id' => $eleve->id, 'date_absence' => $date]);
                $minutes = $request->input("minutes.{$eleve->id}");

                $absence->fill([
                    'etablissement_id' => $classe->etablissement_id,
                    'classe_id' => $classe->id,
                    'type' => $statut,
                    'duree_minutes' => $statut === 'retard' ? ($minutes ?: null) : null,
                    'saisi_par' => $user->id,
                ]);

                $estNouvelle = ! $absence->exists;
                $absence->save();

                if ($estNouvelle) {
                    $nouvelles[] = $absence;
                }
            }
        });

        // Les parents ne sont prévenus qu'une fois par absence, même si l'appel est corrigé ensuite
        foreach ($nouvelles as $absence) {
            Notification::send($absence->eleve->parents, new AbsenceEnregistree($absence));
        }

        return back()->with('succes', 'Appel enregistré.');
    }

    private function elevesInscrits(Classe $classe)
    {
        $ids = Inscription::where('classe_id', $classe->id)->where('statut', 'inscrit')->pluck('eleve_id');

        return Eleve::whereIn('id', $ids)->orderBy('nom')->orderBy('prenoms')->get();
    }
}