<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Etablissement;
use App\Models\Matiere;
use App\Models\Niveau;
use App\Models\Periode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StructureScolaireTest extends TestCase
{
    use RefreshDatabase;

    private function utilisateur(Role $role, Etablissement $etab): User
    {
        return User::factory()->create(['role' => $role, 'etablissement_id' => $etab->id]);
    }

    private function annee(Etablissement $etab, string $libelle = '2026-2027', bool $active = false): AnneeScolaire
    {
        return AnneeScolaire::create([
            'etablissement_id' => $etab->id, 'libelle' => $libelle,
            'date_debut' => '2026-09-14', 'date_fin' => '2027-06-30', 'est_active' => $active,
        ]);
    }

    public function test_le_directeur_peut_ajouter_une_annee_scolaire(): void
    {
        $a = Etablissement::factory()->create();

        $this->actingAs($this->utilisateur(Role::Directeur, $a))
            ->post(route('structure.annees.store'), [
                'libelle' => '2026-2027', 'date_debut' => '2026-09-14', 'date_fin' => '2027-06-30',
            ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('annees_scolaires', ['libelle' => '2026-2027', 'etablissement_id' => $a->id]);
    }

    public function test_une_nouvelle_annee_active_desactive_les_autres(): void
    {
        $a = Etablissement::factory()->create();
        $ancienne = $this->annee($a, '2025-2026', true);

        $this->actingAs($this->utilisateur(Role::Directeur, $a))
            ->post(route('structure.annees.store'), [
                'libelle' => '2026-2027', 'date_debut' => '2026-09-14',
                'date_fin' => '2027-06-30', 'est_active' => '1',
            ])->assertSessionHasNoErrors();

        $this->assertFalse($ancienne->fresh()->est_active);
    }

    public function test_un_enseignant_ne_peut_pas_acceder_a_la_structure(): void
    {
        $a = Etablissement::factory()->create();

        $this->actingAs($this->utilisateur(Role::Enseignant, $a))
            ->get(route('structure.annees.index'))->assertForbidden();
    }

    public function test_un_directeur_ne_voit_que_les_niveaux_de_son_etablissement(): void
    {
        $a = Etablissement::factory()->create();
        $b = Etablissement::factory()->create();
        Niveau::create(['etablissement_id' => $a->id, 'nom' => 'NiveauAlpha', 'ordre' => 1]);
        Niveau::create(['etablissement_id' => $b->id, 'nom' => 'NiveauBeta', 'ordre' => 1]);

        $this->actingAs($this->utilisateur(Role::Directeur, $a))
            ->get(route('structure.niveaux.index'))
            ->assertOk()
            ->assertSee('NiveauAlpha')
            ->assertDontSee('NiveauBeta');
    }

    public function test_un_niveau_du_primaire_peut_etre_ajoute(): void
    {
        $a = Etablissement::factory()->create();

        $this->actingAs($this->utilisateur(Role::Directeur, $a))
            ->post(route('structure.niveaux.store'), ['nom' => 'CP1', 'cycle' => 'primaire', 'ordre' => 1])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('niveaux', ['nom' => 'CP1', 'cycle' => 'primaire', 'etablissement_id' => $a->id]);
    }

    public function test_impossible_de_supprimer_le_niveau_d_un_autre_etablissement(): void
    {
        $a = Etablissement::factory()->create();
        $b = Etablissement::factory()->create();
        $niveauB = Niveau::create(['etablissement_id' => $b->id, 'nom' => '6ème', 'ordre' => 1]);

        $this->actingAs($this->utilisateur(Role::Directeur, $a))
            ->delete(route('structure.niveaux.destroy', $niveauB->id))
            ->assertNotFound();

        $this->assertDatabaseHas('niveaux', ['id' => $niveauB->id]);
    }

    public function test_impossible_de_creer_une_classe_avec_le_niveau_d_un_autre_etablissement(): void
    {
        $a = Etablissement::factory()->create();
        $b = Etablissement::factory()->create();
        $annee = $this->annee($a);
        $niveauB = Niveau::create(['etablissement_id' => $b->id, 'nom' => '6ème', 'ordre' => 1]);

        $this->actingAs($this->utilisateur(Role::Directeur, $a))
            ->post(route('structure.classes.store'), [
                'niveau_id' => $niveauB->id, 'annee_scolaire_id' => $annee->id, 'nom' => '6ème A',
            ])->assertSessionHasErrors('niveau_id');

        $this->assertDatabaseCount('classes', 0);
    }

    public function test_impossible_de_supprimer_un_niveau_qui_contient_des_classes(): void
    {
        $a = Etablissement::factory()->create();
        $annee = $this->annee($a);
        $niveau = Niveau::create(['etablissement_id' => $a->id, 'nom' => '6ème', 'ordre' => 1]);
        Classe::create([
            'etablissement_id' => $a->id, 'niveau_id' => $niveau->id,
            'annee_scolaire_id' => $annee->id, 'nom' => '6ème A',
        ]);

        $this->actingAs($this->utilisateur(Role::Directeur, $a))
            ->delete(route('structure.niveaux.destroy', $niveau))
            ->assertSessionHasErrors('suppression');

        $this->assertDatabaseHas('niveaux', ['id' => $niveau->id]);
    }

    public function test_l_ordre_d_une_periode_est_unique_par_annee(): void
    {
        $a = Etablissement::factory()->create();
        $annee = $this->annee($a);
        Periode::create([
            'etablissement_id' => $a->id, 'annee_scolaire_id' => $annee->id,
            'nom' => 'Trimestre 1', 'ordre' => 1, 'date_debut' => '2026-09-14', 'date_fin' => '2026-12-18',
        ]);

        $this->actingAs($this->utilisateur(Role::Directeur, $a))
            ->post(route('structure.periodes.store'), [
                'annee_scolaire_id' => $annee->id, 'nom' => 'Autre', 'ordre' => 1,
                'date_debut' => '2027-01-04', 'date_fin' => '2027-03-26',
            ])->assertSessionHasErrors('ordre');
    }

    public function test_une_affectation_valide_est_enregistree(): void
    {
        $a = Etablissement::factory()->create();
        $annee = $this->annee($a);
        $niveau = Niveau::create(['etablissement_id' => $a->id, 'nom' => '6ème', 'ordre' => 1]);
        $classe = Classe::create([
            'etablissement_id' => $a->id, 'niveau_id' => $niveau->id,
            'annee_scolaire_id' => $annee->id, 'nom' => '6ème A',
        ]);
        $matiere = Matiere::create(['etablissement_id' => $a->id, 'nom' => 'Mathématiques', 'code' => 'MATH']);
        $prof = $this->utilisateur(Role::Enseignant, $a);

        $this->actingAs($this->utilisateur(Role::Directeur, $a))
            ->post(route('structure.affectations.store'), [
                'classe_id' => $classe->id, 'matiere_id' => $matiere->id,
                'enseignant_id' => $prof->id, 'coefficient' => 4,
            ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('affectations', [
            'classe_id' => $classe->id, 'matiere_id' => $matiere->id, 'enseignant_id' => $prof->id,
        ]);
    }

    public function test_une_affectation_refuse_un_enseignant_d_un_autre_etablissement(): void
    {
        $a = Etablissement::factory()->create();
        $b = Etablissement::factory()->create();
        $annee = $this->annee($a);
        $niveau = Niveau::create(['etablissement_id' => $a->id, 'nom' => '6ème', 'ordre' => 1]);
        $classe = Classe::create([
            'etablissement_id' => $a->id, 'niveau_id' => $niveau->id,
            'annee_scolaire_id' => $annee->id, 'nom' => '6ème A',
        ]);
        $matiere = Matiere::create(['etablissement_id' => $a->id, 'nom' => 'Mathématiques', 'code' => 'MATH']);
        $profB = $this->utilisateur(Role::Enseignant, $b);

        $this->actingAs($this->utilisateur(Role::Directeur, $a))
            ->post(route('structure.affectations.store'), [
                'classe_id' => $classe->id, 'matiere_id' => $matiere->id,
                'enseignant_id' => $profB->id, 'coefficient' => 4,
            ])->assertSessionHasErrors('enseignant_id');

        $this->assertDatabaseCount('affectations', 0);
    }
}