<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\AnneeScolaire;
use App\Models\Bulletin;
use App\Models\Classe;
use App\Models\Eleve;
use App\Models\Inscription;
use App\Models\Periode;
use App\Notifications\BulletinPublie;
use App\Services\BulletinService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;

class BulletinController extends Controller
{
    public function __construct(private BulletinService $bulletins)
    {
    }

    public function index(Request $request)
    {
        $annee = AnneeScolaire::where('est_active', true)->first();
        $classes = $annee ? Classe::where('annee_scolaire_id', $annee->id)->orderBy('nom')->get() : collect();
        $periodes = $annee ? Periode::where('annee_scolaire_id', $annee->id)->orderBy('ordre')->get() : collect();

        $classe = $periode = null;
        $liste = [];

        if ($request->filled('classe_id') && $request->filled('periode_id')) {
            $classe = Classe::findOrFail($request->classe_id);
            $periode = Periode::findOrFail($request->periode_id);
            abort_unless($periode->annee_scolaire_id === $classe->annee_scolaire_id, 404);

            $liste = $this->bulletins->bulletinsDeLaClasse($classe, $periode);
        }

        return view('bulletins.index', compact('classes', 'periodes', 'classe', 'periode', 'liste'));
    }

    public function edit(Eleve $eleve, Periode $periode)
    {
        [$classe, $b] = $this->contexte($eleve, $periode);

        return view('bulletins.edit', compact('classe', 'periode', 'b'));
    }

    public function update(Request $request, Eleve $eleve, Periode $periode)
    {
        [$classe] = $this->contexte($eleve, $periode);

        $donnees = $request->validate([
            'appreciation_generale' => ['nullable', 'string', 'max:500'],
            'decision' => ['nullable', Rule::in(Bulletin::DECISIONS)],
        ]);

        Bulletin::updateOrCreate(
            ['eleve_id' => $eleve->id, 'periode_id' => $periode->id],
            [
                'etablissement_id' => $classe->etablissement_id,
                'classe_id' => $classe->id,
                'appreciation_generale' => $donnees['appreciation_generale'] ?? null,
                'decision' => $donnees['decision'] ?? null,
                'saisi_par' => $request->user()->id,
            ]
        );

        return redirect()
            ->route('bulletins.index', ['classe_id' => $classe->id, 'periode_id' => $periode->id])
            ->with('succes', 'Appréciation et décision enregistrées.');
    }

    public function pdf(Request $request, Eleve $eleve, Periode $periode)
    {
        $user = $request->user();
        $parent = $user->aLeRole(Role::ParentEleve);

        if ($parent) {
            abort_unless($user->enfants()->whereKey($eleve->id)->exists(), 403);
        }

        [, $b] = $this->contexte($eleve, $periode);

        if ($parent) {
            abort_unless($b['bulletin']?->publie, 403);
        }

        return $this->rendre([$b], "bulletin-{$eleve->matricule}-T{$periode->ordre}.pdf");
    }

    public function pdfClasse(Classe $classe, Periode $periode)
    {
        abort_unless($periode->annee_scolaire_id === $classe->annee_scolaire_id, 404);

        $liste = $this->bulletins->bulletinsDeLaClasse($classe, $periode);
        abort_if(empty($liste), 404);

        return $this->rendre($liste, "bulletins-{$classe->nom}-T{$periode->ordre}.pdf");
    }

    public function publier(Classe $classe, Periode $periode)
    {
        abort_unless($periode->annee_scolaire_id === $classe->annee_scolaire_id, 404);

        $liste = $this->bulletins->bulletinsDeLaClasse($classe, $periode);

        if (! collect($liste)->contains(fn ($b) => $b['generale'] !== null)) {
            return back()->withErrors([
                'bulletin' => "Aucune moyenne à publier : validez d'abord des évaluations pour cette période.",
            ]);
        }

        $nouveaux = [];

        foreach ($liste as $eleveId => $b) {
            $bulletin = Bulletin::firstOrNew(['eleve_id' => $eleveId, 'periode_id' => $periode->id]);

            if ($bulletin->publie) {
                continue;
            }

            $bulletin->fill([
                'etablissement_id' => $classe->etablissement_id,
                'classe_id' => $classe->id,
                'publie' => true,
                'publie_le' => now(),
            ])->save();

            $nouveaux[] = $b['eleve'];
        }

        // Les parents ne sont prévenus qu'une seule fois par bulletin
        foreach ($nouveaux as $eleve) {
            Notification::send($eleve->parents, new BulletinPublie($eleve, $periode));
        }

        return back()->with('succes', count($nouveaux) . ' bulletin(s) publié(s) aux parents.');
    }

    public function depublier(Classe $classe, Periode $periode)
    {
        Bulletin::where('classe_id', $classe->id)->where('periode_id', $periode->id)
            ->update(['publie' => false, 'publie_le' => null]);

        return back()->with('succes', 'Publication retirée : les parents ne voient plus ces bulletins.');
    }

    /** Retrouve la classe de l'élève pour l'année de la période, et ses données de bulletin. */
    private function contexte(Eleve $eleve, Periode $periode): array
    {
        $inscription = Inscription::where('eleve_id', $eleve->id)
            ->where('annee_scolaire_id', $periode->annee_scolaire_id)
            ->firstOrFail();

        $classe = Classe::findOrFail($inscription->classe_id);
        $liste = $this->bulletins->bulletinsDeLaClasse($classe, $periode);

        abort_unless(isset($liste[$eleve->id]), 404);

        return [$classe, $liste[$eleve->id]];
    }

    private function rendre(array $bulletins, string $fichier)
    {
        return Pdf::loadView('bulletins.pdf', ['bulletins' => array_values($bulletins)])
            ->setPaper('a4')
            ->stream($fichier);
    }
}