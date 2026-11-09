<?php

namespace App\Http\Controllers;

use App\Models\Absence;
use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Services\PresenceService;
use Illuminate\Http\Request;

class AbsenceController extends Controller
{
    public function index(Request $request, PresenceService $presence)
    {
        $request->validate([
            'classe_id' => ['nullable', 'integer'],
            'du' => ['nullable', 'date'],
            'au' => ['nullable', 'date', 'after_or_equal:du'],
        ]);

        $user = $request->user();
        $annee = AnneeScolaire::where('est_active', true)->first();

        $classes = $annee
            ? Classe::accessiblePar($user)->where('annee_scolaire_id', $annee->id)->orderBy('nom')->get()
            : collect();

        $du = $request->input('du', now()->subDays(30)->toDateString());
        $au = $request->input('au', now()->toDateString());

        $absences = Absence::with(['eleve', 'classe'])
            ->whereIn('classe_id', $classes->pluck('id'))
            ->when($request->filled('classe_id'), fn ($q) => $q->where('classe_id', $request->classe_id))
            ->whereDate('date_absence', '>=', $du)->whereDate('date_absence', '<=', $au)
            ->orderByDesc('date_absence')
            ->limit(300)
            ->get();

        $classeChoisie = $request->filled('classe_id') ? $classes->firstWhere('id', (int) $request->classe_id) : null;
        $stats = $classeChoisie ? $presence->tauxPourClasse($classeChoisie, $du, $au) : null;

        return view('absences.index', compact('classes', 'absences', 'du', 'au', 'stats'));
    }

    public function justifier(Request $request, Absence $absence)
    {
        $donnees = $request->validate(['motif' => ['required', 'string', 'max:255']]);

        $absence->update(['justifiee' => true, 'motif' => $donnees['motif']]);

        return back()->with('succes', 'Absence justifiée.');
    }

    public function retirerJustification(Absence $absence)
    {
        $absence->update(['justifiee' => false, 'motif' => null]);

        return back()->with('succes', 'Justification retirée.');
    }
}