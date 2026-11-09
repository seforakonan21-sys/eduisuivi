<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Affectation;
use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Periode;
use App\Services\CalculMoyennesService;
use Illuminate\Http\Request;

class ResultatController extends Controller
{
    public function index(Request $request, CalculMoyennesService $calcul)
    {
        $user = $request->user();
        $annee = AnneeScolaire::where('est_active', true)->first();

        $classes = $annee
            ? Classe::with('niveau')->where('annee_scolaire_id', $annee->id)
                ->when($user->aLeRole(Role::Enseignant), fn ($q) => $q->whereIn(
                    'id', Affectation::where('enseignant_id', $user->id)->pluck('classe_id')
                ))
                ->orderBy('nom')->get()
            : collect();

        $periodes = $annee
            ? Periode::where('annee_scolaire_id', $annee->id)->orderBy('ordre')->get()
            : collect();

        $classe = $periode = $donnees = $stats = null;

        if ($request->filled('classe_id') && $request->filled('periode_id')) {
            $classe = Classe::with('niveau')->findOrFail($request->classe_id);
            $periode = Periode::findOrFail($request->periode_id);

            abort_unless($periode->annee_scolaire_id === $classe->annee_scolaire_id, 404);

            if ($user->aLeRole(Role::Enseignant)) {
                abort_unless(
                    Affectation::where('classe_id', $classe->id)->where('enseignant_id', $user->id)->exists(),
                    403
                );
            }

            $donnees = $calcul->pourClasse($classe, $periode);

            $generales = collect($donnees['resultats'])->pluck('generale')->filter(fn ($g) => $g !== null);

            $stats = [
                'effectif' => count($donnees['resultats']),
                'moyenne_classe' => $generales->isNotEmpty() ? round($generales->avg(), 2) : null,
                'taux_reussite' => $generales->isNotEmpty()
                    ? round($generales->filter(fn ($g) => $g >= $donnees['bareme'] / 2)->count() / $generales->count() * 100)
                    : null,
                'meilleure' => $generales->max(),
            ];
        }

        return view('resultats.index', compact('classes', 'periodes', 'classe', 'periode', 'donnees', 'stats'));
    }
}