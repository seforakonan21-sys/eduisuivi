<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Absence;
use App\Models\Affectation;
use App\Models\AnneeScolaire;
use App\Models\Appel;
use App\Models\Classe;
use App\Models\Eleve;
use App\Models\Etablissement;
use App\Models\Matiere;
use App\Models\Niveau;
use App\Models\User;
use App\Notifications\AbsenceEnregistree;
use App\Services\InscriptionService;
use App\Services\PresenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AbsencesTest extends TestCase
{
    use RefreshDatabase;

    private function contexte(Etablissement $etab): array
    {
        $annee = AnneeScolaire::create([
            'etablissement_id' => $etab->id, 'libelle' => '2026-2027',
            'date_debut' => '2026-09-14', 'date_fin' => '2027-06-30', 'est_active' => true,
        ]);
        $niveau = Niveau::create([
            'etablissement_id' => $etab->id, 'nom' => 'CM2', 'cycle' => 'primaire', 'bareme' => 10, 'ordre' => 6,
        ]);
        $classe = Classe::create([
            'etablissement_id' => $etab->id, 'niveau_id' => $niveau->id,
            'annee_scolaire_id' => $annee->id, 'nom' => 'CM2 A',
        ]);
        $matiere = Matiere::create(['etablissement_id' => $etab->id, 'nom' => 'Français', 'code' => 'FRA']);
        $prof = User::factory()->create(['role' => Role::Enseignant, 'etablissement_id' => $etab->id]);
        Affectation::create([
            'etablissement_id' => $etab->id, 'classe_id' => $classe->id, 'matiere_id' => $matiere->id,
            'enseignant_id' => $prof->id, 'coefficient' => 1,
        ]);

        return ['etab' => $etab, 'annee' => $annee, 'niveau' => $niveau, 'classe' => $classe, 'prof' => $prof];
    }

    private function eleve(array $ctx, string $nom, ?Classe $classe = null): Eleve
    {
        $eleve = Eleve::create([
            'etablissement_id' => $ctx['etab']->id,
            'matricule' => Eleve::genererMatricule($ctx['etab']),
            'nom' => $nom, 'prenoms' => 'Awa', 'date_naissance' => '2016-03-14', 'sexe' => 'F',
        ]);
        app(InscriptionService::class)->inscrire($eleve, $classe ?? $ctx['classe']);

        return $eleve;
    }

    private function absence(array $ctx, Eleve $eleve, string $date, string $type = 'absence', ?Classe $classe = null): Absence
    {
        return Absence::create([
            'etablissement_id' => $ctx['etab']->id, 'eleve_id' => $eleve->id,
            'classe_id' => ($classe ?? $ctx['classe'])->id, 'date_absence' => $date, 'type' => $type,
        ]);
    }

    private function utilisateur(Role $role, Etablissement $etab): User
    {
        return User::factory()->create(['role' => $role, 'etablissement_id' => $etab->id]);
    }

    private function hier(): string
    {
        return now()->subDay()->toDateString();
    }

    // ----- Appel -----

    public function test_l_enseignant_enregistre_l_appel_de_sa_classe(): void
    {
        $ctx = $this->contexte(Etablissement::factory()->create());
        $a = $this->eleve($ctx, 'Alpha');
        $b = $this->eleve($ctx, 'Bravo');
        $c = $this->eleve($ctx, 'Charlie');

        $this->actingAs($ctx['prof'])->post(route('appel.store'), [
            'classe_id' => $ctx['classe']->id, 'date' => $this->hier(),
            'statuts' => [$a->id => 'absence', $b->id => 'present', $c->id => 'retard'],
            'minutes' => [$c->id => 15],
        ])->assertSessionHasNoErrors();

        $this->assertSame(2, Absence::withoutGlobalScopes()->count());
        $this->assertSame('absence', Absence::withoutGlobalScopes()->where('eleve_id', $a->id)->value('type'));
        $this->assertSame(15, Absence::withoutGlobalScopes()->where('eleve_id', $c->id)->value('duree_minutes'));
        $this->assertSame(1, Appel::withoutGlobalScopes()->count());
    }

    public function test_un_appel_peut_etre_corrige(): void
    {
        $ctx = $this->contexte(Etablissement::factory()->create());
        $a = $this->eleve($ctx, 'Alpha');

        $envoyer = fn (string $statut) => $this->actingAs($ctx['prof'])->post(route('appel.store'), [
            'classe_id' => $ctx['classe']->id, 'date' => $this->hier(),
            'statuts' => [$a->id => $statut],
        ]);

        $envoyer('absence');
        $this->assertSame(1, Absence::withoutGlobalScopes()->count());

        $envoyer('present');
        $this->assertSame(0, Absence::withoutGlobalScopes()->count());
    }

    public function test_passer_d_absent_a_retard_ne_cree_pas_de_doublon(): void
    {
        $ctx = $this->contexte(Etablissement::factory()->create());
        $a = $this->eleve($ctx, 'Alpha');

        foreach (['absence', 'retard'] as $statut) {
            $this->actingAs($ctx['prof'])->post(route('appel.store'), [
                'classe_id' => $ctx['classe']->id, 'date' => $this->hier(),
                'statuts' => [$a->id => $statut], 'minutes' => [$a->id => 10],
            ]);
        }

        $this->assertSame(1, Absence::withoutGlobalScopes()->count());
        $this->assertSame('retard', Absence::withoutGlobalScopes()->value('type'));
    }

    public function test_les_parents_sont_prevenus_une_seule_fois(): void
    {
        $ctx = $this->contexte(Etablissement::factory()->create());
        $a = $this->eleve($ctx, 'Alpha');
        $parent = $this->utilisateur(Role::ParentEleve, $ctx['etab']);
        $a->parents()->attach($parent->id, ['lien' => 'mere']);

        $envoyer = fn () => $this->actingAs($ctx['prof'])->post(route('appel.store'), [
            'classe_id' => $ctx['classe']->id, 'date' => $this->hier(),
            'statuts' => [$a->id => 'absence'],
        ]);

        $envoyer();
        $this->assertSame(1, $parent->notifications()->count());

        $envoyer(); // appel renvoyé à l'identique : pas de nouvelle notification
        $this->assertSame(1, $parent->notifications()->count());
    }

    public function test_le_parent_d_un_autre_eleve_n_est_pas_prevenu(): void
    {
        $ctx = $this->contexte(Etablissement::factory()->create());
        $a = $this->eleve($ctx, 'Alpha');
        $b = $this->eleve($ctx, 'Bravo');
        $parentDeB = $this->utilisateur(Role::ParentEleve, $ctx['etab']);
        $b->parents()->attach($parentDeB->id, ['lien' => 'pere']);

        $this->actingAs($ctx['prof'])->post(route('appel.store'), [
            'classe_id' => $ctx['classe']->id, 'date' => $this->hier(),
            'statuts' => [$a->id => 'absence', $b->id => 'present'],
        ]);

        $this->assertSame(0, $parentDeB->notifications()->count());
    }

    public function test_un_enseignant_ne_peut_pas_faire_l_appel_d_une_classe_qui_n_est_pas_la_sienne(): void
    {
        $ctx = $this->contexte(Etablissement::factory()->create());
        $a = $this->eleve($ctx, 'Alpha');
        $autreProf = $this->utilisateur(Role::Enseignant, $ctx['etab']);

        $this->actingAs($autreProf)->post(route('appel.store'), [
            'classe_id' => $ctx['classe']->id, 'date' => $this->hier(),
            'statuts' => [$a->id => 'absence'],
        ])->assertNotFound();

        $this->assertSame(0, Absence::withoutGlobalScopes()->count());
    }

    public function test_un_appel_dans_le_futur_est_refuse(): void
    {
        $ctx = $this->contexte(Etablissement::factory()->create());
        $a = $this->eleve($ctx, 'Alpha');

        $this->actingAs($ctx['prof'])->post(route('appel.store'), [
            'classe_id' => $ctx['classe']->id, 'date' => now()->addDay()->toDateString(),
            'statuts' => [$a->id => 'absence'],
        ])->assertSessionHasErrors('date');
    }

    public function test_un_statut_invalide_est_refuse(): void
    {
        $ctx = $this->contexte(Etablissement::factory()->create());
        $a = $this->eleve($ctx, 'Alpha');

        $this->actingAs($ctx['prof'])->post(route('appel.store'), [
            'classe_id' => $ctx['classe']->id, 'date' => $this->hier(),
            'statuts' => [$a->id => 'malade'],
        ])->assertSessionHasErrors("statuts.{$a->id}");
    }

    // ----- Justification -----

    public function test_le_directeur_justifie_une_absence_et_l_enseignant_ne_le_peut_pas(): void
    {
        $ctx = $this->contexte(Etablissement::factory()->create());
        $absence = $this->absence($ctx, $this->eleve($ctx, 'Alpha'), $this->hier());

        $this->actingAs($ctx['prof'])
            ->patch(route('absences.justifier', $absence), ['motif' => 'Maladie'])
            ->assertForbidden();

        $this->actingAs($this->utilisateur(Role::Directeur, $ctx['etab']))
            ->patch(route('absences.justifier', $absence), ['motif' => 'Maladie'])
            ->assertSessionHasNoErrors();

        $absence = Absence::withoutGlobalScopes()->find($absence->id);
        $this->assertTrue($absence->justifiee);
        $this->assertSame('Maladie', $absence->motif);
    }

    public function test_la_justification_exige_un_motif(): void
    {
        $ctx = $this->contexte(Etablissement::factory()->create());
        $absence = $this->absence($ctx, $this->eleve($ctx, 'Alpha'), $this->hier());

        $this->actingAs($this->utilisateur(Role::Directeur, $ctx['etab']))
            ->patch(route('absences.justifier', $absence), ['motif' => ''])
            ->assertSessionHasErrors('motif');
    }

    public function test_la_justification_peut_etre_retiree(): void
    {
        $ctx = $this->contexte(Etablissement::factory()->create());
        $absence = $this->absence($ctx, $this->eleve($ctx, 'Alpha'), $this->hier());
        $absence->update(['justifiee' => true, 'motif' => 'Maladie']);

        $this->actingAs($this->utilisateur(Role::Directeur, $ctx['etab']))
            ->delete(route('absences.retirer', $absence))
            ->assertSessionHasNoErrors();

        $this->assertFalse(Absence::withoutGlobalScopes()->find($absence->id)->justifiee);
    }

    // ----- Statistiques et historique -----

    public function test_le_taux_de_presence_se_calcule_sur_les_jours_d_appel(): void
    {
        $ctx = $this->contexte(Etablissement::factory()->create());
        $eleves = [
            $this->eleve($ctx, 'Alpha'), $this->eleve($ctx, 'Bravo'),
            $this->eleve($ctx, 'Charlie'), $this->eleve($ctx, 'Delta'),
        ];
        $jour1 = now()->subDay()->toDateString();
        $jour2 = now()->subDays(2)->toDateString();

        foreach ([$jour1, $jour2] as $jour) {
            Appel::create(['etablissement_id' => $ctx['etab']->id, 'classe_id' => $ctx['classe']->id, 'date_appel' => $jour]);
        }

        $this->absence($ctx, $eleves[0], $jour1);
        $this->absence($ctx, $eleves[1], $jour2);
        $this->absence($ctx, $eleves[2], $jour1, 'retard'); // un retard compte comme présent

        $res = app(PresenceService::class)->tauxPourClasse(
            $ctx['classe'], now()->subDays(3)->toDateString(), now()->toDateString()
        );

        $this->assertSame(2, $res['jours']);
        $this->assertSame(2, $res['absences']);
        $this->assertSame(1, $res['retards']);
        $this->assertEquals(75.0, $res['taux']); // (4 élèves x 2 jours - 2 absences) / 8
    }

    public function test_un_enseignant_ne_voit_que_les_absences_de_ses_classes(): void
    {
        $ctx = $this->contexte(Etablissement::factory()->create());
        $autreClasse = Classe::create([
            'etablissement_id' => $ctx['etab']->id, 'niveau_id' => $ctx['niveau']->id,
            'annee_scolaire_id' => $ctx['annee']->id, 'nom' => 'CM2 B',
        ]);
        $alpha = $this->eleve($ctx, 'Alpha');
        $zeta = $this->eleve($ctx, 'Zeta', $autreClasse);
        $this->absence($ctx, $alpha, $this->hier());
        $this->absence($ctx, $zeta, $this->hier(), 'absence', $autreClasse);

        $this->actingAs($ctx['prof'])
            ->get(route('absences.index'))
            ->assertOk()
            ->assertSee('Alpha')
            ->assertDontSee('Zeta');
    }

    public function test_un_directeur_ne_voit_pas_les_absences_d_un_autre_etablissement(): void
    {
        $a = Etablissement::factory()->create();
        $b = Etablissement::factory()->create();
        $ctx = $this->contexte($a);
        $ctxB = $this->contexte($b);
        $this->absence($ctx, $this->eleve($ctx, 'Alpha'), $this->hier());
        $this->absence($ctxB, $this->eleve($ctxB, 'Bravo'), $this->hier());

        $this->actingAs($this->utilisateur(Role::Directeur, $a))
            ->get(route('absences.index'))
            ->assertSee('Alpha')
            ->assertDontSee('Bravo');
    }

    // ----- Espace parent et notifications -----

    public function test_un_parent_ne_voit_que_les_absences_de_ses_enfants(): void
    {
        $ctx = $this->contexte(Etablissement::factory()->create());
        $alpha = $this->eleve($ctx, 'Alpha');
        $zeta = $this->eleve($ctx, 'Zeta');
        $this->absence($ctx, $alpha, $this->hier());
        $this->absence($ctx, $zeta, $this->hier());

        $parent = $this->utilisateur(Role::ParentEleve, $ctx['etab']);
        $alpha->parents()->attach($parent->id, ['lien' => 'mere']);

        $this->actingAs($parent)
            ->get(route('espace.absences'))
            ->assertOk()
            ->assertSee('Alpha')
            ->assertDontSee('Zeta');
    }

    public function test_le_parent_consulte_et_lit_ses_notifications(): void
    {
        $ctx = $this->contexte(Etablissement::factory()->create());
        $alpha = $this->eleve($ctx, 'Alpha');
        $absence = $this->absence($ctx, $alpha, $this->hier());
        $parent = $this->utilisateur(Role::ParentEleve, $ctx['etab']);
        $parent->notify(new AbsenceEnregistree($absence));

        $this->actingAs($parent)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Absence de Alpha Awa');

        $this->assertSame(1, $parent->unreadNotifications()->count());

        $this->actingAs($parent)->post(route('notifications.lues'));

        $this->assertSame(0, $parent->unreadNotifications()->count());
    }
}