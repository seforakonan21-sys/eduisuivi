<?php

namespace App\Services;

use App\Models\Absence;
use App\Models\Appreciation;
use App\Models\Bulletin;
use App\Models\Classe;
use App\Models\Periode;
use Illuminate\Support\Facades\Storage;

class BulletinService
{
    public function __construct(private CalculMoyennesService $calcul)
    {
    }

    /** Toutes les données du bulletin de chaque élève inscrit dans la classe, indexées par id d'élève. */
    public function bulletinsDeLaClasse(Classe $classe, Periode $periode): array
    {
        $classe->loadMissing(['niveau', 'anneeScolaire', 'etablissement']);
        $etablissement = $classe->etablissement;

        $calcul = $this->calcul->pourClasse($classe, $periode);
        $bareme = $calcul['bareme'];
        $affectations = $calcul['affectations'];
        $resultats = collect($calcul['resultats']);
        $effectif = $resultats->count();

        $moyennesClasse = [];
        foreach ($affectations as $affectation) {
            $valeurs = $resultats->map(fn ($r) => $r['moyennes'][$affectation->id] ?? null)
                ->filter(fn ($v) => $v !== null);
            $moyennesClasse[$affectation->id] = $valeurs->isNotEmpty() ? round($valeurs->avg(), 2) : null;
        }

        $generales = $resultats->pluck('generale')->filter(fn ($g) => $g !== null);
        $moyenneClasse = $generales->isNotEmpty() ? round($generales->avg(), 2) : null;
        $idsEleves = $resultats->pluck('eleve.id');

        $appreciations = Appreciation::where('periode_id', $periode->id)
            ->whereIn('affectation_id', $affectations->pluck('id'))
            ->get()->groupBy('eleve_id');

        $bulletins = Bulletin::where('periode_id', $periode->id)
            ->whereIn('eleve_id', $idsEleves)->get()->keyBy('eleve_id');

        $absences = Absence::where('classe_id', $classe->id)
            ->whereIn('eleve_id', $idsEleves)
            ->whereDate('date_absence', '>=', $periode->date_debut->toDateString())
            ->whereDate('date_absence', '<=', $periode->date_fin->toDateString())
            ->get()->groupBy('eleve_id');

        $logo = $this->imageEnBase64($etablissement->logo);
        $cachet = $this->imageEnBase64($etablissement->cachet);

        $liste = [];

        foreach ($resultats as $r) {
            $eleve = $r['eleve'];
            $absencesEleve = $absences->get($eleve->id, collect());
            $textes = ($appreciations->get($eleve->id) ?? collect())->keyBy('affectation_id');
            $bulletin = $bulletins->get($eleve->id);

            $liste[$eleve->id] = [
                'etablissement' => $etablissement,
                'logo' => $logo,
                'cachet' => $cachet,
                'eleve' => $eleve,
                'classe' => $classe,
                'periode' => $periode,
                'annee' => $classe->anneeScolaire,
                'bareme' => $bareme,
                'effectif' => $effectif,
                'matieres' => $affectations->map(fn ($aff) => [
                    'nom' => $aff->matiere->nom,
                    'coefficient' => (float) $aff->coefficient,
                    'moyenne' => $r['moyennes'][$aff->id] ?? null,
                    'moyenne_classe' => $moyennesClasse[$aff->id],
                    'appreciation' => $textes->get($aff->id)?->texte,
                ])->all(),
                'generale' => $r['generale'],
                'rang' => $r['rang'],
                'moyenne_classe' => $moyenneClasse,
                'meilleure' => $generales->max(),
                'plus_faible' => $generales->min(),
                'mention' => $this->mention($r['generale'], $bareme),
                'absences' => [
                    'absences' => $absencesEleve->where('type', 'absence')->count(),
                    'justifiees' => $absencesEleve->where('type', 'absence')->where('justifiee', true)->count(),
                    'retards' => $absencesEleve->where('type', 'retard')->count(),
                    'minutes_retard' => (int) $absencesEleve->where('type', 'retard')->sum('duree_minutes'),
                ],
                'appreciation_generale' => $bulletin?->appreciation_generale,
                'decision' => $bulletin?->decision,
                'bulletin' => $bulletin,
            ];
        }

        return $liste;
    }

    private function mention(?float $generale, int $bareme): ?string
    {
        if ($generale === null) {
            return null;
        }

        $ratio = $generale / $bareme;

        return match (true) {
            $ratio >= 0.8 => 'Très bien',
            $ratio >= 0.7 => 'Bien',
            $ratio >= 0.6 => 'Assez bien',
            $ratio >= 0.5 => 'Passable',
            default => 'Insuffisant',
        };
    }

    /** Les images sont intégrées au PDF en base64 : pas besoin d'accès réseau ni de lien public. */
    private function imageEnBase64(?string $chemin): ?string
    {
        if (! $chemin || ! Storage::disk('public')->exists($chemin)) {
            return null;
        }

        $mime = Storage::disk('public')->mimeType($chemin) ?: 'image/png';

        return 'data:' . $mime . ';base64,' . base64_encode(Storage::disk('public')->get($chemin));
    }
}