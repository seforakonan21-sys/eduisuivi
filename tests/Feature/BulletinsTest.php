<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Absence;
use App\Models\Affectation;
use App\Models\AnneeScolaire;
use App\Models\Appreciation;
use App\Models\Bulletin;
use App\Models\Classe;
use App\Models\Eleve;
use App\Models\Etablissement;
use App\Models\Evaluation;
use App\Models\Matiere;
use App\Models\Niveau;
use App\Models\Note;
use App\Models\Periode;
use App\Models\User;
use App\Services\BulletinService;
use App\Services\InscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BulletinsTest extends TestCase
{
    use RefreshDatabase;

    private function contexte(Etablissement $etab): array
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
            'etablissement_id' => $etab->id, 'nom' => '6ème', 'cycle' => 'college', 'bareme' => 20, 'ordre' => 1,
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

        return ['etab' => $etab, 'periode' => $periode, 'classe' => $classe, 'prof' => $prof, 'aff' => $affectation];
    }

    private function eleve(array $ctx, string $nom): Eleve
    {
        $eleve = Eleve::create([
            'etablissement_id' => $ctx['etab']->id,
            'matricule' => Eleve::genererMatricule($ctx['etab']),
            'nom' => $nom, 'prenoms' => 'Awa', 'date_naissance' => '2012-03-14', 'sexe' => 'F',
        ]);
        app(InscriptionService::class)->inscrire($eleve, $ctx['classe']);

        return $eleve;
    }

    /** Une évaluation validée sur 20 avec les notes données (id d'élève => note). */
    private function noter(array $ctx, array $notes): void
    {
        $evaluation = Evaluation::create([
            'etablissement_id' => $ctx['etab']->id, 'affectation_id' => $ctx['aff']->id,
            'periode_id' => $ctx['periode']->id, 'titre' => 'Devoir', 'type' => 'devoir',
            'note_max' => 20, 'date_evaluation' => '2026-10-12', 'statut' => 'valide',
        ]);

        foreach ($notes as $eleveId => $valeur) {
            Note::create([
                'etablissement_id' => $ctx['etab']->id, 'evaluation_id' => $evaluation->id,
                'eleve_id' => $eleveId, 'valeur' => $valeur,
            ]);
        }
    }

    private function utilisateur(Role $role, Etablissement $etab): User
    {
        return User::factory()->create(['role' => $role, 'etablissement_id' => $etab->id]);
    }

    private function liste(array $ctx): array
    {
        return app(BulletinService::class)->bulletinsDeLaClasse($ctx['classe'], $ctx['periode']);
    }

    private function publierDirectement(array $ctx, Eleve $eleve): void
    {
        Bulletin::create([
            'etablissement_id' => $ctx['etab']->id, 'eleve_id' => $eleve->id,
            'classe_id' => $ctx['classe']->id, 'periode_id' => $ctx['periode']->id,
            'publie' => true, 'publie_le' => now(),
        ]);
    }

    // ----- Contenu du bulletin -----

    public function test_le_bulletin_contient_moyennes_rang_et_moyenne_de_classe(): void
    {
        $ctx = $this->contexte(Etablissement::factory()->create());
        $alpha = $this->eleve($ctx, 'Alpha');
        $bravo = $this->eleve($ctx, 'Bravo');
        $charlie = $this->eleve($ctx, 'Charlie');
        $this->noter($ctx, [$alpha->id => 16, $bravo->id => 12, $charlie->id => 12]);

        $liste = $this->liste($ctx);
        $bulletin = $liste[$alpha->id];

        $this->assertEquals(16.0, $bulletin['generale']);
        $this->assertSame(1, $bulletin['rang']);
        $this->assertSame(3, $bulletin['effectif']);
        $this->assertEquals(13.33, $bulletin['moyenne_classe']);
        $this->assertEquals(13.33, $bulletin['matieres'][0]['moyenne_classe']);
        $this->assertEquals(16.0, $bulletin['meilleure']);
        $this->assertEquals(12.0, $bulletin['plus_faible']);
        $this->assertSame(2, $liste[$bravo->id]['rang']);
        $this->assertSame(2, $liste[$charlie->id]['rang']);
    }

    public function test_la_mention_est_calculee_selon_le_bareme(): void
    {
        $ctx = $this->contexte(Etablissement::factory()->create());
        $alpha = $this->eleve($ctx, 'Alpha');
        $bravo = $this->eleve($ctx, 'Bravo');
        $this->noter($ctx, [$alpha->id => 16, $bravo->id => 12]);

        $liste = $this->liste($ctx);

        $this->assertSame('Très bien', $liste[$alpha->id]['mention']);
        $this->assertSame('Assez bien', $liste[$bravo->id]['mention']);
    }

    public function test_les_absences_sont_comptees_sur_la_periode_uniquement(): void
    {
        $ctx = $this->contexte(Etablissement::factory()->create());
        $alpha = $this->eleve($ctx, 'Alpha');
        $this->noter($ctx, [$alpha->id => 15]);

        $creer = fn (string $date, string $type, bool $justifiee = false, ?int $minutes = null) => Absence::create([
            'etablissement_id' => $ctx['etab']->id, 'eleve_id' => $alpha->id, 'classe_id' => $ctx['classe']->id,
            'date_absence' => $date, 'type' => $type, 'justifiee' => $justifiee, 'duree_minutes' => $minutes,
        ]);

        $creer('2026-10-05', 'absence');
        $creer('2026-10-06', 'absence', true);
        $creer('2026-10-07', 'retard', false, 20);
        $creer('2027-01-10', 'absence'); // hors période : ne compte pas

        $absences = $this->liste($ctx)[$alpha->id]['absences'];

        $this->assertSame(2, $absences['absences']);
        $this->assertSame(1, $absences['justifiees']);
        $this->assertSame(1, $absences['retards']);
        $this->assertSame(20, $absences['minutes_retard']);
    }

    // ----- Appréciations -----

    public function test_l_enseignant_saisit_les_appreciations_de_sa_matiere(): void
    {
        $ctx = $this->contexte(Etablissement::factory()->create());
        $alpha = $this->eleve($ctx, 'Alpha');
        $this->noter($ctx, [$alpha->id => 15]);

        $this->actingAs($ctx['prof'])->put(route('appreciations.update'), [
            'affectation_id' => $ctx['aff']->id, 'periode_id' => $ctx['periode']->id,
            'appreciations' => [$alpha->id => 'Très bon travail'],
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('appreciations', ['eleve_id' => $alpha->id, 'texte' => 'Très bon travail']);
        $this->assertSame('Très bon travail', $this->liste($ctx)[$alpha->id]['matieres'][0]['appreciation']);
    }

    public function test_un_enseignant_ne_peut_pas_saisir_les_appreciations_d_un_collegue(): void
    {
        $ctx = $this->contexte(Etablissement::factory()->create());
        $alpha = $this->eleve($ctx, 'Alpha');
        $collegue = $this->utilisateur(Role::Enseignant, $ctx['etab']);

        $this->actingAs($collegue)->put(route('appreciations.update'), [
            'affectation_id' => $ctx['aff']->id, 'periode_id' => $ctx['periode']->id,
            'appreciations' => [$alpha->id => 'Intrus'],
        ])->assertNotFound();

        $this->assertSame(0, Appreciation::withoutGlobalScopes()->count());
    }

    public function test_le_directeur_enregistre_l_appreciation_generale_et_la_decision(): void
    {
        $ctx = $this->contexte(Etablissement::factory()->create());
        $alpha = $this->eleve($ctx, 'Alpha');
        $this->noter($ctx, [$alpha->id => 15]);

        $this->actingAs($this->utilisateur(Role::Directeur, $ctx['etab']))
            ->put(route('bulletins.update', [$alpha, $ctx['periode']]), [
                'appreciation_generale' => 'Élève sérieux.', 'decision' => 'Félicitations',
            ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('bulletins', [
            'eleve_id' => $alpha->id, 'decision' => 'Félicitations', 'appreciation_generale' => 'Élève sérieux.',
        ]);
    }

    public function test_une_decision_inconnue_est_refusee(): void
    {
        $ctx = $this->contexte(Etablissement::factory()->create());
        $alpha = $this->eleve($ctx, 'Alpha');
        $this->noter($ctx, [$alpha->id => 15]);

        $this->actingAs($this->utilisateur(Role::Directeur, $ctx['etab']))
            ->put(route('bulletins.update', [$alpha, $ctx['periode']]), ['decision' => 'Champion du monde'])
            ->assertSessionHasErrors('decision');
    }

    // ----- PDF et droits d'accès -----

    public function test_le_directeur_telecharge_le_pdf_d_un_eleve(): void
    {
        $ctx = $this->contexte(Etablissement::factory()->create());
        $alpha = $this->eleve($ctx, 'Alpha');
        $this->noter($ctx, [$alpha->id => 15]);

        $reponse = $this->actingAs($this->utilisateur(Role::Directeur, $ctx['etab']))
            ->get(route('bulletins.pdf', [$alpha, $ctx['periode']]))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertStringStartsWith('%PDF', $reponse->getContent());
    }

    public function test_le_pdf_de_toute_la_classe_est_genere(): void
    {
        $ctx = $this->contexte(Etablissement::factory()->create());
        $alpha = $this->eleve($ctx, 'Alpha');
        $bravo = $this->eleve($ctx, 'Bravo');
        $this->noter($ctx, [$alpha->id => 15, $bravo->id => 11]);

        $this->actingAs($this->utilisateur(Role::Directeur, $ctx['etab']))
            ->get(route('bulletins.pdf.classe', [$ctx['classe'], $ctx['periode']]))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_un_enseignant_ne_peut_pas_telecharger_de_bulletin(): void
    {
        $ctx = $this->contexte(Etablissement::factory()->create());
        $alpha = $this->eleve($ctx, 'Alpha');

        $this->actingAs($ctx['prof'])
            ->get(route('bulletins.pdf', [$alpha, $ctx['periode']]))
            ->assertForbidden();
    }

    public function test_un_parent_ne_telecharge_le_bulletin_qu_une_fois_publie(): void
    {
        $ctx = $this->contexte(Etablissement::factory()->create());
        $alpha = $this->eleve($ctx, 'Alpha');
        $this->noter($ctx, [$alpha->id => 15]);
        $parent = $this->utilisateur(Role::ParentEleve, $ctx['etab']);
        $alpha->parents()->attach($parent->id, ['lien' => 'mere']);

        $this->actingAs($parent)
            ->get(route('bulletins.pdf', [$alpha, $ctx['periode']]))
            ->assertForbidden();

        $this->publierDirectement($ctx, $alpha);

        $this->actingAs($parent)
            ->get(route('bulletins.pdf', [$alpha, $ctx['periode']]))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_un_parent_ne_peut_pas_telecharger_le_bulletin_d_un_autre_enfant(): void
    {
        $ctx = $this->contexte(Etablissement::factory()->create());
        $alpha = $this->eleve($ctx, 'Alpha');
        $zeta = $this->eleve($ctx, 'Zeta');
        $this->noter($ctx, [$alpha->id => 15, $zeta->id => 12]);
        $this->publierDirectement($ctx, $zeta);
        $parent = $this->utilisateur(Role::ParentEleve, $ctx['etab']);
        $alpha->parents()->attach($parent->id, ['lien' => 'mere']);

        $this->actingAs($parent)
            ->get(route('bulletins.pdf', [$zeta, $ctx['periode']]))
            ->assertForbidden();
    }

    public function test_le_personnel_administratif_telecharge_mais_ne_publie_pas(): void
    {
        $ctx = $this->contexte(Etablissement::factory()->create());
        $alpha = $this->eleve($ctx, 'Alpha');
        $this->noter($ctx, [$alpha->id => 15]);
        $secretariat = $this->utilisateur(Role::Administratif, $ctx['etab']);

        $this->actingAs($secretariat)
            ->get(route('bulletins.pdf', [$alpha, $ctx['periode']]))
            ->assertOk();

        $this->actingAs($secretariat)
            ->post(route('bulletins.publier', [$ctx['classe'], $ctx['periode']]))
            ->assertForbidden();
    }

    public function test_le_directeur_d_un_autre_etablissement_ne_trouve_pas_le_bulletin(): void
    {
        $a = Etablissement::factory()->create();
        $b = Etablissement::factory()->create();
        $ctx = $this->contexte($a);
        $this->contexte($b);
        $alpha = $this->eleve($ctx, 'Alpha');
        $this->noter($ctx, [$alpha->id => 15]);

        $this->actingAs($this->utilisateur(Role::Directeur, $b))
            ->get(route('bulletins.pdf', [$alpha->id, $ctx['periode']->id]))
            ->assertNotFound();
    }

    // ----- Publication -----

    public function test_la_publication_previent_les_parents_une_seule_fois(): void
    {
        $ctx = $this->contexte(Etablissement::factory()->create());
        $alpha = $this->eleve($ctx, 'Alpha');
        $this->noter($ctx, [$alpha->id => 15]);
        $parent = $this->utilisateur(Role::ParentEleve, $ctx['etab']);
        $alpha->parents()->attach($parent->id, ['lien' => 'mere']);
        $directeur = $this->utilisateur(Role::Directeur, $ctx['etab']);

        $publier = fn () => $this->actingAs($directeur)
            ->post(route('bulletins.publier', [$ctx['classe'], $ctx['periode']]));

        $publier()->assertSessionHasNoErrors();
        $this->assertTrue((bool) Bulletin::withoutGlobalScopes()->where('eleve_id', $alpha->id)->value('publie'));
        $this->assertSame(1, $parent->notifications()->count());

        $publier(); // deuxième publication : pas de nouvelle notification
        $this->assertSame(1, $parent->notifications()->count());
    }

    public function test_la_publication_peut_etre_retiree(): void
    {
        $ctx = $this->contexte(Etablissement::factory()->create());
        $alpha = $this->eleve($ctx, 'Alpha');
        $this->noter($ctx, [$alpha->id => 15]);
        $this->publierDirectement($ctx, $alpha);

        $this->actingAs($this->utilisateur(Role::Directeur, $ctx['etab']))
            ->delete(route('bulletins.depublier', [$ctx['classe'], $ctx['periode']]))
            ->assertSessionHasNoErrors();

        $this->assertFalse((bool) Bulletin::withoutGlobalScopes()->where('eleve_id', $alpha->id)->value('publie'));
    }

    public function test_la_publication_est_refusee_sans_aucune_moyenne(): void
    {
        $ctx = $this->contexte(Etablissement::factory()->create());
        $this->eleve($ctx, 'Alpha');

        $this->actingAs($this->utilisateur(Role::Directeur, $ctx['etab']))
            ->post(route('bulletins.publier', [$ctx['classe'], $ctx['periode']]))
            ->assertSessionHasErrors('bulletin');

        $this->assertSame(0, Bulletin::withoutGlobalScopes()->count());
    }

    // ----- Liste et établissement -----

    public function test_la_liste_des_bulletins_affiche_les_eleves_et_leurs_moyennes(): void
    {
        $ctx = $this->contexte(Etablissement::factory()->create());
        $alpha = $this->eleve($ctx, 'Alpha');
        $this->noter($ctx, [$alpha->id => 16]);

        $this->actingAs($this->utilisateur(Role::Directeur, $ctx['etab']))
            ->get(route('bulletins.index', ['classe_id' => $ctx['classe']->id, 'periode_id' => $ctx['periode']->id]))
            ->assertOk()
            ->assertSee('Alpha')
            ->assertSee('16,00');
    }

    public function test_le_directeur_met_a_jour_les_informations_de_l_etablissement(): void
    {
        $etab = Etablissement::factory()->create();

        $this->actingAs($this->utilisateur(Role::Directeur, $etab))
            ->put(route('etablissement.update'), [
                'adresse' => 'BP 12 Divo', 'signataire_nom' => 'M. Kouassi',
            ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('etablissements', [
            'id' => $etab->id, 'adresse' => 'BP 12 Divo', 'signataire_nom' => 'M. Kouassi',
        ]);
    }
}