<?php

namespace App\Services;

use App\Models\Absence;
use App\Models\Appel;
use App\Models\Classe;
use App\Models\Inscription;

class PresenceService
{
    /**
     * Taux de présence d'une classe entre deux dates.
     * Il se calcule sur les jours où l'appel a été fait : un retard compte comme présent.
     */
    public function tauxPourClasse(Classe $classe, string $du, string $au): array
    {
        $jours = Appel::where('classe_id', $classe->id)
            ->whereDate('date_appel', '>=', $du)->whereDate('date_appel', '<=', $au)
            ->count();

        $effectif = Inscription::where('classe_id', $classe->id)->where('statut', 'inscrit')->count();

        $compter = fn (string $type) => Absence::where('classe_id', $classe->id)
            ->where('type', $type)
            ->whereDate('date_absence', '>=', $du)->whereDate('date_absence', '<=', $au)
            ->count();

        $absences = $compter('absence');
        $retards = $compter('retard');
        $attendu = $jours * $effectif;

        return [
            'jours' => $jours,
            'effectif' => $effectif,
            'absences' => $absences,
            'retards' => $retards,
            'taux' => $attendu > 0 ? round(max(0, $attendu - $absences) / $attendu * 100, 1) : null,
        ];
    }

    /** Taux de présence de toutes les classes de l'année en cours. */
    public function tauxGlobal(string $du, string $au): ?float
    {
        $attendu = 0;
        $absences = 0;

        $classes = Classe::whereHas('anneeScolaire', fn ($q) => $q->where('est_active', true))->get();

        foreach ($classes as $classe) {
            $r = $this->tauxPourClasse($classe, $du, $au);
            $attendu += $r['jours'] * $r['effectif'];
            $absences += $r['absences'];
        }

        return $attendu > 0 ? round(max(0, $attendu - $absences) / $attendu * 100, 1) : null;
    }
}