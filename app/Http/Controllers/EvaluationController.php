<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Affectation;
use App\Models\AnneeScolaire;
use App\Models\Evaluation;
use App\Models\Periode;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EvaluationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $evaluations = Evaluation::with(['affectation.classe', 'affectation.matiere', 'periode'])
            ->withCount('notes')
            ->when($user->aLeRole(Role::Enseignant), fn ($q) => $q->whereHas(
                'affectation', fn ($a) => $a->where('enseignant_id', $user->id)
            ))
            ->orderByDesc('date_evaluation')->orderByDesc('id')
            ->get();

        return view('evaluations.index', compact('evaluations'));
    }

    public function create(Request $request)
    {
        $user = $request->user();
        $annee = AnneeScolaire::where('est_active', true)->first();
        $affectations = collect();
        $periodes = collect();

        if ($annee) {
            $affectations = Affectation::with(['classe.niveau', 'matiere'])
                ->whereHas('classe', fn ($q) => $q->where('annee_scolaire_id', $annee->id))
                ->when($user->aLeRole(Role::Enseignant), fn ($q) => $q->where('enseignant_id', $user->id))
                ->get()
                ->sortBy(fn ($a) => $a->classe->nom . $a->matiere->nom);

            $periodes = Periode::where('annee_scolaire_id', $annee->id)->orderBy('ordre')->get();
        }

        return view('evaluations.create', compact('annee', 'affectations', 'periodes'));
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $etab = $user->etablissement_id;

        $donnees = $request->validate([
            'affectation_id' => ['required', Rule::exists('affectations', 'id')
                ->where('etablissement_id', $etab)
                ->when($user->aLeRole(Role::Enseignant), fn ($r) => $r->where('enseignant_id', $user->id))],
            'periode_id' => ['required', Rule::exists('periodes', 'id')->where('etablissement_id', $etab)],
            'titre' => ['required', 'string', 'max:120'],
            'type' => ['required', Rule::in(array_keys(Evaluation::TYPES))],
            'note_max' => ['required', Rule::in([10, 20])],
            'date_evaluation' => ['required', 'date'],
        ]);

        $affectation = Affectation::with('classe')->findOrFail($donnees['affectation_id']);
        $periode = Periode::findOrFail($donnees['periode_id']);

        if ($periode->annee_scolaire_id !== $affectation->classe->annee_scolaire_id) {
            throw ValidationException::withMessages([
                'periode_id' => "Cette période n'appartient pas à l'année scolaire de la classe.",
            ]);
        }

        Evaluation::create([...$donnees, 'statut' => 'brouillon']);

        return redirect()->route('evaluations.index')
            ->with('succes', 'Évaluation créée. Vous pouvez maintenant saisir les notes.');
    }

    public function destroy(Request $request, Evaluation $evaluation)
    {
        abort_unless($evaluation->accessiblePar($request->user()), 403);

        if ($evaluation->estValidee()) {
            return back()->withErrors([
                'evaluation' => 'Impossible de supprimer une évaluation validée : dévalidez-la d\'abord.',
            ]);
        }

        $evaluation->delete();

        return back()->with('succes', 'Évaluation supprimée.');
    }

    public function valider(Evaluation $evaluation)
    {
        if (! $evaluation->notes()->whereNotNull('valeur')->exists()) {
            return back()->withErrors(['evaluation' => 'Impossible de valider une évaluation sans aucune note.']);
        }

        $evaluation->update(['statut' => 'valide']);

        return back()->with('succes', 'Évaluation validée : ses notes comptent maintenant dans les moyennes.');
    }

    public function devalider(Evaluation $evaluation)
    {
        $evaluation->update(['statut' => 'brouillon']);

        return back()->with('succes', 'Évaluation repassée en brouillon : elle ne compte plus dans les moyennes.');
    }
}