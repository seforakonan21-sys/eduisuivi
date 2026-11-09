<?php

namespace App\Services;

use App\Models\Affectation;
use App\Models\Classe;
use App\Models\Eleve;
use App\Models\Evaluation;
use App\Models\Inscription;
use App\Models\Periode;

class CalculMoyennesService
{
    /**
     * Calcule, pour une classe et une période, les moyennes par matière,
     * la moyenne générale et le rang de chaque élève.
     * Seules les évaluations VALIDÉES comptent, et chaque note est convertie
     * dans le barème du niveau (ex. 8/10 devient 16/20 dans un niveau sur 20).
     */
    public function pourClasse(Classe $classe, Periode $periode): array
    {
        $bareme = (int) $classe->niveau->bareme;

        $affectations = Affectation::with('matiere')
            ->where('classe_id', $classe->id)
            ->get()
            ->sortBy(fn ($a) => $a->matiere->nom)
            ->values();

        $evaluations = Evaluation::with('notes')
            ->where('periode_id', $periode->id)
            ->where('statut', 'valide')
            ->whereIn('affectation_id', $affectations->pluck('id'))
            ->get()
            ->groupBy('affectation_id');

        $idsEleves = Inscription::where('classe_id', $classe->id)
            ->where('statut', 'inscrit')
            ->pluck('eleve_id');

        $eleves = Eleve::whereIn('id', $idsEleves)->orderBy('nom')->orderBy('prenoms')->get();

        $resultats = [];

        foreach ($eleves as $eleve) {
            $moyennes = [];
            $somme = 0.0;
            $sommeCoefficients = 0.0;

            foreach ($affectations as $affectation) {
                $valeurs = [];

                foreach ($evaluations->get($affectation->id, collect()) as $evaluation) {
                    $note = $evaluation->notes->firstWhere('eleve_id', $eleve->id);

                    if ($note && $note->valeur !== null) {
                        $valeurs[] = ((float) $note->valeur) * $bareme / $evaluation->note_max;
                    }
                }

                $moyenne = $valeurs ? round(array_sum($valeurs) / count($valeurs), 2) : null;
                $moyennes[$affectation->id] = $moyenne;

                if ($moyenne !== null) {
                    $coefficient = (float) $affectation->coefficient;
                    $somme += $moyenne * $coefficient;
                    $sommeCoefficients += $coefficient;
                }
            }

            $resultats[] = [
                'eleve' => $eleve,
                'moyennes' => $moyennes,
                'generale' => $sommeCoefficients > 0 ? round($somme / $sommeCoefficients, 2) : null,
                'rang' => null,
            ];
        }

        // Rang : les ex æquo partagent le même rang (1, 2, 2, 4...)
        $tries = collect($resultats)->filter(fn ($r) => $r['generale'] !== null)
            ->sortByDesc('generale')->values();

        $rangs = [];
        $dernierRang = 0;
        $derniereMoyenne = null;

        foreach ($tries as $position => $r) {
            $rang = ($derniereMoyenne !== null && $r['generale'] == $derniereMoyenne)
                ? $dernierRang
                : $position + 1;

            $rangs[$r['eleve']->id] = $rang;
            $dernierRang = $rang;
            $derniereMoyenne = $r['generale'];
        }

        $resultats = array_map(
            fn ($r) => [...$r, 'rang' => $rangs[$r['eleve']->id] ?? null],
            $resultats
        );

        return ['bareme' => $bareme, 'affectations' => $affectations, 'resultats' => $resultats];
    }
}