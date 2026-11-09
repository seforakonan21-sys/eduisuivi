<?php

namespace App\Http\Controllers;

use App\Models\Eleve;
use App\Models\Evaluation;
use App\Models\Inscription;
use App\Models\Note;
use Illuminate\Http\Request;

class NoteController extends Controller
{
    public function edit(Request $request, Evaluation $evaluation)
    {
        abort_unless($evaluation->accessiblePar($request->user()), 403);

        $evaluation->load(['affectation.classe', 'affectation.matiere', 'periode']);

        return view('evaluations.notes', [
            'evaluation' => $evaluation,
            'eleves' => $this->elevesDeLaClasse($evaluation),
            'notes' => $evaluation->notes->pluck('valeur', 'eleve_id'),
        ]);
    }

    public function update(Request $request, Evaluation $evaluation)
    {
        abort_unless($evaluation->accessiblePar($request->user()), 403);

        if ($evaluation->estValidee()) {
            return back()->withErrors([
                'evaluation' => 'Cette évaluation est validée : elle ne peut plus être modifiée.',
            ]);
        }

        $request->validate([
            'notes' => ['required', 'array'],
            'notes.*' => ['nullable', 'numeric', 'min:0', 'max:' . $evaluation->note_max],
        ]);

        foreach ($this->elevesDeLaClasse($evaluation) as $eleve) {
            $valeur = $request->input("notes.{$eleve->id}");

            if ($valeur === null || $valeur === '') {
                Note::where('evaluation_id', $evaluation->id)->where('eleve_id', $eleve->id)->delete();
                continue;
            }

            Note::updateOrCreate(
                ['evaluation_id' => $evaluation->id, 'eleve_id' => $eleve->id],
                ['etablissement_id' => $evaluation->etablissement_id, 'valeur' => $valeur]
            );
        }

        return back()->with('succes', 'Notes enregistrées.');
    }

    private function elevesDeLaClasse(Evaluation $evaluation)
    {
        $ids = Inscription::where('classe_id', $evaluation->affectation->classe_id)
            ->where('statut', 'inscrit')
            ->pluck('eleve_id');

        return Eleve::whereIn('id', $ids)->orderBy('nom')->orderBy('prenoms')->get();
    }
}