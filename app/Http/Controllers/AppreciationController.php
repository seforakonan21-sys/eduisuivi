<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Affectation;
use App\Models\AnneeScolaire;
use App\Models\Appreciation;
use App\Models\Eleve;
use App\Models\Inscription;
use App\Models\Periode;
use App\Models\User;
use Illuminate\Http\Request;

class AppreciationController extends Controller
{
    public function edit(Request $request)
    {
        $annee = AnneeScolaire::where('est_active', true)->first();

        $affectations = $this->affectationsAccessibles($request->user(), $annee)->get()
            ->sortBy(fn ($a) => $a->classe->nom . $a->matiere->nom);
        $periodes = $annee ? Periode::where('annee_scolaire_id', $annee->id)->orderBy('ordre')->get() : collect();

        $affectation = $periode = null;
        $eleves = collect();
        $textes = collect();

        if ($request->filled('affectation_id') && $request->filled('periode_id')) {
            [$affectation, $periode] = $this->charger($request, $annee);
            $eleves = $this->eleves($affectation);
            $textes = Appreciation::where('affectation_id', $affectation->id)
                ->where('periode_id', $periode->id)->pluck('texte', 'eleve_id');
        }

        return view('appreciations.edit', compact('affectations', 'periodes', 'affectation', 'periode', 'eleves', 'textes'));
    }

    public function update(Request $request)
    {
        $annee = AnneeScolaire::where('est_active', true)->first();
        [$affectation, $periode] = $this->charger($request, $annee);

        $request->validate([
            'appreciations' => ['nullable', 'array'],
            'appreciations.*' => ['nullable', 'string', 'max:255'],
        ]);

        foreach ($this->eleves($affectation) as $eleve) {
            $cle = ['eleve_id' => $eleve->id, 'affectation_id' => $affectation->id, 'periode_id' => $periode->id];
            $texte = trim((string) $request->input("appreciations.{$eleve->id}"));

            if ($texte === '') {
                Appreciation::where($cle)->delete();
                continue;
            }

            Appreciation::updateOrCreate($cle, [
                'etablissement_id' => $affectation->etablissement_id,
                'texte' => $texte,
                'saisi_par' => $request->user()->id,
            ]);
        }

        return back()->with('succes', 'Appréciations enregistrées.');
    }

    private function affectationsAccessibles(User $user, ?AnneeScolaire $annee)
    {
        return Affectation::with(['classe', 'matiere'])
            ->whereHas('classe', fn ($q) => $q->where('annee_scolaire_id', $annee?->id))
            ->when($user->aLeRole(Role::Enseignant), fn ($q) => $q->where('enseignant_id', $user->id));
    }

    private function charger(Request $request, ?AnneeScolaire $annee): array
    {
        $request->validate([
            'affectation_id' => ['required', 'integer'],
            'periode_id' => ['required', 'integer'],
        ]);

        $affectation = $this->affectationsAccessibles($request->user(), $annee)->findOrFail($request->affectation_id);
        $periode = Periode::findOrFail($request->periode_id);

        abort_unless($periode->annee_scolaire_id === $affectation->classe->annee_scolaire_id, 404);

        return [$affectation, $periode];
    }

    private function eleves(Affectation $affectation)
    {
        $ids = Inscription::where('classe_id', $affectation->classe_id)->where('statut', 'inscrit')->pluck('eleve_id');

        return Eleve::whereIn('id', $ids)->orderBy('nom')->orderBy('prenoms')->get();
    }
}