<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Eleve;
use App\Models\Etablissement;
use App\Models\Inscription;
use App\Models\Niveau;
use App\Models\User;
use App\Services\InscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ElevesTest extends TestCase
{
    use RefreshDatabase;

    private function utilisateur(Role $role, Etablissement $etab): User
    {
        return User::factory()->create(['role' => $role, 'etablissement_id' => $etab->id]);
    }

    private function classe(Etablissement $etab, bool $anneeActive = true, ?int $effectifMax = null): Classe
    {
        $annee = AnneeScolaire::create([
            'etablissement_id' => $etab->id, 'libelle' => '2026-2027',
            'date_debut' => '2026-09-14', 'date_fin' => '2027-06-30', 'est_active' => $anneeActive,
        ]);
        $niveau = Niveau::create(['etablissement_id' => $etab->id, 'nom' => '6ème', 'ordre' => 1]);

        return Classe::create([
            'etablissement_id' => $etab->id, 'niveau_id' => $niveau->id,
            'annee_scolaire_id' => $annee->id, 'nom' => '6ème A', 'effectif_max' => $effectifMax,
        ]);
    }

    private function eleve(Etablissement $etab, string $nom = 'Test'): Eleve
    {
        return Eleve::create([
            'etablissement_id' => $etab->id,
            'matricule' => Eleve::genererMatricule($etab),
            'nom' => $nom, 'prenoms' => 'Awa', 'date_naissance' => '2012-03-14', 'sexe' => 'F',
        ]);
    }

    private function donnees(Classe $classe, array $extra = []): array
    {
        return array_merge([
            'nom' => 'Kouassi', 'prenoms' => 'Awa', 'date_naissance' => '2012-03-14',
            'sexe' => 'F', 'classe_id' => $classe->id,
        ], $extra);
    }

    public function test_le_directeur_inscrit_un_nouvel_eleve(): void
    {
        $a = Etablissement::factory()->create();
        $classe = $this->classe($a);

        $this->actingAs($this->utilisateur(Role::Directeur, $a))
            ->post(route('eleves.store'), $this->donnees($classe))
            ->assertSessionHasNoErrors();

        $eleve = Eleve::withoutGlobalScopes()->firstOrFail();
        $this->assertStringStartsWith($a->code . now()->format('y') . '-', $eleve->matricule);
        $this->assertDatabaseHas('inscriptions', [
            'eleve_id' => $eleve->id, 'classe_id' => $classe->id, 'statut' => 'inscrit',
        ]);
    }

    public function test_les_matricules_sont_incrementes(): void
    {
        $a = Etablissement::factory()->create();
        $classe = $this->classe($a);
        $directeur = $this->utilisateur(Role::Directeur, $a);

        $this->actingAs($directeur)->post(route('eleves.store'), $this->donnees($classe, ['nom' => 'Premier']));
        $this->actingAs($directeur)->post(route('eleves.store'), $this->donnees($classe, ['nom' => 'Second']));

        $matricules = Eleve::withoutGlobalScopes()->orderBy('id')->pluck('matricule');
        $this->assertCount(2, $matricules);
        $this->assertStringEndsWith('-0001', $matricules[0]);
        $this->assertStringEndsWith('-0002', $matricules[1]);
    }

    public function test_une_classe_complete_refuse_l_inscription(): void
    {
        $a = Etablissement::factory()->create();
        $classe = $this->classe($a, true, 1);
        app(InscriptionService::class)->inscrire($this->eleve($a), $classe);

        $this->actingAs($this->utilisateur(Role::Directeur, $a))
            ->post(route('eleves.store'), $this->donnees($classe))
            ->assertSessionHasErrors('classe_id');

        $this->assertDatabaseCount('eleves', 1);
    }

    public function test_impossible_d_inscrire_dans_la_classe_d_un_autre_etablissement(): void
    {
        $a = Etablissement::factory()->create();
        $b = Etablissement::factory()->create();
        $classeB = $this->classe($b);
        $this->classe($a);

        $this->actingAs($this->utilisateur(Role::Directeur, $a))
            ->post(route('eleves.store'), $this->donnees($classeB))
            ->assertSessionHasErrors('classe_id');

        $this->assertDatabaseCount('eleves', 0);
    }

    public function test_impossible_d_inscrire_sans_annee_active(): void
    {
        $a = Etablissement::factory()->create();
        $classe = $this->classe($a, false);

        $this->actingAs($this->utilisateur(Role::Directeur, $a))
            ->post(route('eleves.store'), $this->donnees($classe))
            ->assertSessionHasErrors('classe_id');

        $this->assertDatabaseCount('eleves', 0);
    }

    public function test_un_directeur_ne_voit_pas_les_eleves_d_un_autre_etablissement(): void
    {
        $a = Etablissement::factory()->create();
        $b = Etablissement::factory()->create();
        $this->eleve($a, 'Alphaname');
        $this->eleve($b, 'Betaname');

        $this->actingAs($this->utilisateur(Role::Directeur, $a))
            ->get(route('eleves.index'))
            ->assertOk()
            ->assertSee('Alphaname')
            ->assertDontSee('Betaname');
    }

    public function test_le_dossier_d_un_eleve_d_un_autre_etablissement_est_introuvable(): void
    {
        $a = Etablissement::factory()->create();
        $b = Etablissement::factory()->create();
        $eleveB = $this->eleve($b);

        $this->actingAs($this->utilisateur(Role::Directeur, $a))
            ->get(route('eleves.show', $eleveB->id))
            ->assertNotFound();
    }

    public function test_la_recherche_filtre_les_eleves(): void
    {
        $a = Etablissement::factory()->create();
        $this->eleve($a, 'Kouassi');
        $this->eleve($a, 'Traore');

        $this->actingAs($this->utilisateur(Role::Directeur, $a))
            ->get(route('eleves.index', ['q' => 'Kouassi']))
            ->assertSee('Kouassi')
            ->assertDontSee('Traore');
    }

    public function test_creation_d_un_compte_parent_avec_mot_de_passe_temporaire(): void
    {
        $a = Etablissement::factory()->create();

        $this->actingAs($this->utilisateur(Role::Directeur, $a))
            ->post(route('parents.store'), [
                'name' => 'Madame Konan', 'email' => 'konan.parent@test.ci',
            ])->assertSessionHasNoErrors();

        $parent = User::where('email', 'konan.parent@test.ci')->firstOrFail();
        $this->assertSame(Role::ParentEleve, $parent->role);
        $this->assertSame($a->id, $parent->etablissement_id);
        $this->assertTrue($parent->doit_changer_mot_de_passe);

        $motDePasse = session('mot_de_passe_temporaire');
        $this->assertNotEmpty($motDePasse);
        $this->assertTrue(Hash::check($motDePasse, $parent->password));
    }

    public function test_un_parent_peut_suivre_plusieurs_enfants(): void
    {
        $a = Etablissement::factory()->create();
        $directeur = $this->utilisateur(Role::Directeur, $a);
        $parent = $this->utilisateur(Role::ParentEleve, $a);
        $premier = $this->eleve($a, 'Premier');
        $second = $this->eleve($a, 'Second');

        foreach ([$premier, $second] as $enfant) {
            $this->actingAs($directeur)
                ->post(route('eleves.parents.store', $enfant), ['parent_id' => $parent->id, 'lien' => 'mere'])
                ->assertSessionHasNoErrors();
        }

        $this->assertCount(2, $parent->enfants);
    }

    public function test_impossible_de_lier_un_parent_d_un_autre_etablissement(): void
    {
        $a = Etablissement::factory()->create();
        $b = Etablissement::factory()->create();
        $parentB = $this->utilisateur(Role::ParentEleve, $b);
        $eleve = $this->eleve($a);

        $this->actingAs($this->utilisateur(Role::Directeur, $a))
            ->post(route('eleves.parents.store', $eleve), ['parent_id' => $parentB->id, 'lien' => 'pere'])
            ->assertSessionHasErrors('parent_id');

        $this->assertDatabaseCount('eleve_parent', 0);
    }

    public function test_un_enseignant_ne_peut_pas_acceder_aux_eleves(): void
    {
        $a = Etablissement::factory()->create();

        $this->actingAs($this->utilisateur(Role::Enseignant, $a))
            ->get(route('eleves.index'))
            ->assertForbidden();
    }

    public function test_le_statut_d_une_inscription_peut_etre_modifie(): void
    {
        $a = Etablissement::factory()->create();
        $classe = $this->classe($a);
        $inscription = app(InscriptionService::class)->inscrire($this->eleve($a), $classe);

        $this->actingAs($this->utilisateur(Role::Directeur, $a))
            ->patch(route('inscriptions.update', $inscription), ['statut' => 'abandon'])
            ->assertSessionHasNoErrors();

        $this->assertSame('abandon', Inscription::withoutGlobalScopes()->find($inscription->id)->statut);
    }
}