<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Affectation;
use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Eleve;
use App\Models\Etablissement;
use App\Models\Evaluation;
use App\Models\Matiere;
use App\Models\Niveau;
use App\Models\Note;
use App\Models\Periode;
use App\Models\User;
use App\Services\CalculMoyennesService;
use App\Services\InscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotesTest extends TestCase
{
    use RefreshDatabase;

    private function contexte(Etablissement $etab, int $bareme = 20): array
    {
        $annee = AnneeScolaire::create([
            'etablissement_id' => $etab->id, 'libelle' => '2026-2027',
            'date_debut' => '2026-09-14', 'date_fin' => '2027-06-30', 'est_active' => true,
        ]);
        $periode = Periode::create([
            'etablissement_id' => $etab->id, 'annee_scolaire_id' => $annee->id,
            'nom' => 'Trimestre 1', 'ordre' => 1, 'date_debut' => '2026-09-14', 'date_fin' => '2026-12-18',
        ]);
        $niveau = Niveau::create([
            'etablissement_id' => $etab->id, 'nom' => '6ème', 'cycle' => 'college',
            'bareme' => $bareme, 'ordre' => 1,
        ]);
        $classe = Classe::create([
            'etablissement_id' => $etab->id, 'niveau_id' => $niveau->id,
            'annee_scolaire_id' => $annee->id, 'nom' => '6ème A',
        ]);
        $matiere = Matiere::create(['etablissement_id' => $etab->id, 'nom' => 'Mathématiques', 'code' => 'MATH']);
        $prof = User::factory()->create(['role' => Role::Enseignant, 'etablissement_id' => $etab->id]);
        $affectation = Affectation::create([
            'etablissement_id' => $etab->id, 'classe_id' => $classe->id, 'matiere_id' => $matiere->id,
            'enseignant_id' => $prof->id, 'coefficient' => 4,
        ]);

        return ['etab' => $etab, 'annee' => $annee, 'periode' => $periode, 'classe' => $classe,
                'prof' => $prof, 'aff' => $affectation];
    }

    private function autreAffectation(array $ctx, string $code, int $coefficient, ?User $prof = null): Affectation
    {
        $matiere = Matiere::create(['etablissement_id' => $ctx['etab']->id, 'nom' => "Matière {$code}", 'code' => $code]);

        return Affectation::create([
            'etablissement_id' => $ctx['etab']->id, 'classe_id' => $ctx['classe']->id,
            'matiere_id' => $matiere->id, 'enseignant_id' => ($prof ?? $ctx['prof'])->id,
            'coefficient' => $coefficient,
        ]);
    }

    private function eleve(array $ctx, string $nom = 'Eleve'): Eleve
    {
        $eleve = Eleve::create([
            'etablissement_id' => $ctx['etab']->id,
            'matricule' => Eleve::genererMatricule($ctx['etab']),
            'nom' => $nom, 'prenoms' => 'Awa', 'date_naissance' => '2012-03-14', 'sexe' => 'F',
        ]);
        app(InscriptionService::class)->inscrire($eleve, $ctx['classe']);

        return $eleve;
    }

    private function evaluation(array $ctx, int $noteMax, array $notes, string $statut = 'valide',
                                string $titre = 'Devoir', ?Affectation $aff = null): Evaluation
    {
        $aff ??= $ctx['aff'];

        $evaluation = Evaluation::create([
            'etablissement_id' => $ctx['etab']->id, 'affectation_id' => $aff->id,
            'periode_id' => $ctx['periode']->id, 'titre' => $titre, 'type' => 'devoir',
            'note_max' => $noteMax, 'date_evaluation' => '2026-10-12', 'statut' => $statut,
        ]);

        foreach ($notes as $eleveId => $valeur) {
            Note::create([
                'etablissement_id' => $ctx['etab']->id, 'evaluation_id' => $evaluation->id,
                'eleve_id' => $eleveId, 'valeur' => $valeur,
            ]);
        }

        return $evaluation;
    }

    private function calcul(array $ctx): array
    {
        return app(CalculMoyennesService::class)->pourClasse($ctx['classe'], $ctx['periode']);
    }

    private function directeur(Etablissement $etab): User
    {
        return User::factory()->create(['role' => Role::Directeur, 'etablissement_id' => $etab->id]);
    }

    // ----- Calcul -----

    public function test_les_notes_sur_10_et_sur_20_sont_converties_dans_le_bareme_du_niveau(): void
    {
        $ctx = $this->contexte(Etablissement::factory()->create(), 20);
        $eleve = $this->eleve($ctx);
        $this->evaluation($ctx, 10, [$eleve->id => 8]);   // 8/10 vaut 16/20
        $this->evaluation($ctx, 20, [$eleve->id => 12]);  // 12/20

        $res = $this->calcul($ctx);

        $this->assertEquals(14.0, $res['resultats'][0]['moyennes'][$ctx['aff']->id]);
        $this->assertEquals(14.0, $res['resultats'][0]['generale']);
    }

    public function test_la_moyenne_generale_tient_compte_des_coefficients(): void
    {
        $ctx = $this->contexte(Etablissement::factory()->create());
        $eleve = $this->eleve($ctx);
        $francais = $this->autreAffectation($ctx, 'FRA', 1);
        $this->evaluation($ctx, 20, [$eleve->id => 15]);                                       // coef 4
        $this->evaluation($ctx, 20, [$eleve->id => 10], 'valide', 'Devoir FR', $francais);     // coef 1

        $res = $this->calcul($ctx);

        $this->assertEquals(14.0, $res['resultats'][0]['generale']); // (15*4 + 10*1) / 5
    }

    public function test_les_evaluations_en_brouillon_ne_comptent_pas(): void
    {
        $ctx = $this->contexte(Etablissement::factory()->create());
        $eleve = $this->eleve($ctx);
        $this->evaluation($ctx, 20, [$eleve->id => 18], 'brouillon');

        $res = $this->calcul($ctx);

        $this->assertNull($res['resultats'][0]['moyennes'][$ctx['aff']->id]);
        $this->assertNull($res['resultats'][0]['generale']);
    }

    public function test_les_ex_aequo_partagent_le_meme_rang(): void
    {
        $ctx = $this->contexte(Etablissement::factory()->create());
        $a = $this->eleve($ctx, 'Alpha');
        $b = $this->eleve($ctx, 'Bravo');
        $c = $this->eleve($ctx, 'Charlie');
        $this->evaluation($ctx, 20, [$a->id => 15, $b->id => 15, $c->id => 10]);

        $rangs = collect($this->calcul($ctx)['resultats'])
            ->mapWithKeys(fn ($r) => [$r['eleve']->id => $r['rang']])->all();

        $this->assertSame([$a->id => 1, $b->id => 1, $c->id => 3], $rangs);
    }

    public function test_un_niveau_sur_10_garde_les_notes_sur_10(): void
    {
        $ctx = $this->contexte(Etablissement::factory()->create(), 10);
        $eleve = $this->eleve($ctx);
        $this->evaluation($ctx, 10, [$eleve->id => 8]);

        $res = $this->calcul($ctx);

        $this->assertSame(10, $res['bareme']);
        $this->assertEquals(8.0, $res['resultats'][0]['generale']);
    }

    // ----- Saisie -----

    public function test_un_enseignant_saisit_les_notes_de_sa_classe(): void
    {
        $ctx = $this->contexte(Etablissement::factory()->create());
        $eleve = $this->eleve($ctx);
        $evaluation = $this->evaluation($ctx, 20, [], 'brouillon');

        $this->actingAs($ctx['prof'])
            ->put(route('evaluations.notes.update', $evaluation), ['notes' => [$eleve->id => '12']])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('notes', [
            'evaluation_id' => $evaluation->id, 'eleve_id' => $eleve->id, 'valeur' => 12,
        ]);
    }

    public function test_une_note_superieure_au_maximum_est_refusee(): void
    {
        $ctx = $this->contexte(Etablissement::factory()->create(), 10);
        $eleve = $this->eleve($ctx);
        $evaluation = $this->evaluation($ctx, 10, [], 'brouillon');

        $this->actingAs($ctx['prof'])
            ->put(route('evaluations.notes.update', $evaluation), ['notes' => [$eleve->id => '15']])
            ->assertSessionHasErrors("notes.{$eleve->id}");

        $this->assertDatabaseCount('notes', 0);
    }

    public function test_un_enseignant_ne_peut_pas_saisir_les_notes_d_un_collegue(): void
    {
        $a = Etablissement::factory()->create();
        $ctx = $this->contexte($a);
        $eleve = $this->eleve($ctx);
        $evaluation = $this->evaluation($ctx, 20, [], 'brouillon');
        $collegue = User::factory()->create(['role' => Role::Enseignant, 'etablissement_id' => $a->id]);

        $this->actingAs($collegue)
            ->put(route('evaluations.notes.update', $evaluation), ['notes' => [$eleve->id => '12']])
            ->assertForbidden();
    }

    public function test_les_notes_d_une_evaluation_validee_sont_verrouillees(): void
    {
        $ctx = $this->contexte(Etablissement::factory()->create());
        $eleve = $this->eleve($ctx);
        $evaluation = $this->evaluation($ctx, 20, [$eleve->id => 8], 'valide');

        $this->actingAs($ctx['prof'])
            ->put(route('evaluations.notes.update', $evaluation), ['notes' => [$eleve->id => '19']])
            ->assertSessionHasErrors('evaluation');

        $this->assertDatabaseHas('notes', ['evaluation_id' => $evaluation->id, 'valeur' => 8]);
    }

    // ----- Validation -----

    public function test_seul_le_directeur_peut_valider_une_evaluation(): void
    {
        $a = Etablissement::factory()->create();
        $ctx = $this->contexte($a);
        $eleve = $this->eleve($ctx);
        $evaluation = $this->evaluation($ctx, 20, [$eleve->id => 12], 'brouillon');

        $this->actingAs($ctx['prof'])
            ->patch(route('evaluations.valider', $evaluation))
            ->assertForbidden();

        $this->actingAs($this->directeur($a))
            ->patch(route('evaluations.valider', $evaluation))
            ->assertSessionHasNoErrors();

        $this->assertSame('valide', Evaluation::withoutGlobalScopes()->find($evaluation->id)->statut);
    }

    public function test_impossible_de_valider_une_evaluation_sans_note(): void
    {
        $a = Etablissement::factory()->create();
        $ctx = $this->contexte($a);
        $evaluation = $this->evaluation($ctx, 20, [], 'brouillon');

        $this->actingAs($this->directeur($a))
            ->patch(route('evaluations.valider', $evaluation))
            ->assertSessionHasErrors('evaluation');

        $this->assertSame('brouillon', Evaluation::withoutGlobalScopes()->find($evaluation->id)->statut);
    }

    // ----- Création et visibilité des évaluations -----

    public function test_un_enseignant_cree_une_evaluation_pour_sa_classe(): void
    {
        $ctx = $this->contexte(Etablissement::factory()->create());

        $this->actingAs($ctx['prof'])
            ->post(route('evaluations.store'), [
                'affectation_id' => $ctx['aff']->id, 'periode_id' => $ctx['periode']->id,
                'titre' => 'Devoir 1', 'type' => 'devoir', 'note_max' => 20, 'date_evaluation' => '2026-10-12',
            ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('evaluations', ['titre' => 'Devoir 1', 'statut' => 'brouillon']);
    }

    public function test_un_enseignant_ne_peut_pas_creer_une_evaluation_pour_la_classe_d_un_collegue(): void
    {
        $a = Etablissement::factory()->create();
        $ctx = $this->contexte($a);
        $collegue = User::factory()->create(['role' => Role::Enseignant, 'etablissement_id' => $a->id]);
        $affectationCollegue = $this->autreAffectation($ctx, 'FRA', 2, $collegue);

        $this->actingAs($ctx['prof'])
            ->post(route('evaluations.store'), [
                'affectation_id' => $affectationCollegue->id, 'periode_id' => $ctx['periode']->id,
                'titre' => 'Intrus', 'type' => 'devoir', 'note_max' => 20, 'date_evaluation' => '2026-10-12',
            ])->assertSessionHasErrors('affectation_id');

        $this->assertDatabaseCount('evaluations', 0);
    }

    public function test_la_periode_d_un_autre_etablissement_est_refusee(): void
    {
        $a = Etablissement::factory()->create();
        $b = Etablissement::factory()->create();
        $ctx = $this->contexte($a);
        $ctxB = $this->contexte($b);

        $this->actingAs($ctx['prof'])
            ->post(route('evaluations.store'), [
                'affectation_id' => $ctx['aff']->id, 'periode_id' => $ctxB['periode']->id,
                'titre' => 'Devoir', 'type' => 'devoir', 'note_max' => 20, 'date_evaluation' => '2026-10-12',
            ])->assertSessionHasErrors('periode_id');
    }

    public function test_un_enseignant_ne_voit_que_ses_evaluations(): void
    {
        $a = Etablissement::factory()->create();
        $ctx = $this->contexte($a);
        $collegue = User::factory()->create(['role' => Role::Enseignant, 'etablissement_id' => $a->id]);
        $affectationCollegue = $this->autreAffectation($ctx, 'FRA', 2, $collegue);

        $this->evaluation($ctx, 20, [], 'brouillon', 'EvalAlpha');
        $this->evaluation($ctx, 20, [], 'brouillon', 'EvalBeta', $affectationCollegue);

        $this->actingAs($ctx['prof'])
            ->get(route('evaluations.index'))
            ->assertOk()
            ->assertSee('EvalAlpha')
            ->assertDontSee('EvalBeta');
    }

    // ----- Résultats -----

    public function test_le_directeur_consulte_les_resultats_d_une_classe(): void
    {
        $a = Etablissement::factory()->create();
        $ctx = $this->contexte($a);
        $eleve = $this->eleve($ctx);
        $this->evaluation($ctx, 20, [$eleve->id => 14]);

        $this->actingAs($this->directeur($a))
            ->get(route('resultats.index', ['classe_id' => $ctx['classe']->id, 'periode_id' => $ctx['periode']->id]))
            ->assertOk()
            ->assertSee('14,00');
    }

    public function test_les_resultats_d_une_classe_d_un_autre_etablissement_sont_introuvables(): void
    {
        $a = Etablissement::factory()->create();
        $b = Etablissement::factory()->create();
        $ctx = $this->contexte($a);
        $ctxB = $this->contexte($b);

        $this->actingAs($this->directeur($a))
            ->get(route('resultats.index', ['classe_id' => $ctxB['classe']->id, 'periode_id' => $ctx['periode']->id]))
            ->assertNotFound();
    }
}