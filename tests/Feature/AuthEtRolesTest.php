<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Etablissement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthEtRolesTest extends TestCase
{
    use RefreshDatabase;

    private function creerUser(Role $role, ?Etablissement $etab = null, array $extra = []): User
    {
        return User::factory()->create(array_merge([
            'role' => $role,
            'etablissement_id' => $etab?->id,
        ], $extra));
    }

    public function test_la_page_de_connexion_s_affiche(): void
    {
        $this->get('/connexion')->assertOk()->assertSee('Se connecter');
    }

    public function test_connexion_avec_bons_identifiants(): void
    {
        $user = $this->creerUser(Role::Directeur, Etablissement::factory()->create());

        $this->post('/connexion', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_connexion_refusee_avec_mauvais_mot_de_passe(): void
    {
        $user = $this->creerUser(Role::Enseignant);

        $this->post('/connexion', ['email' => $user->email, 'password' => 'faux'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_un_compte_desactive_ne_peut_pas_se_connecter(): void
    {
        $user = $this->creerUser(Role::Enseignant, null, ['actif' => false]);

        $this->post('/connexion', ['email' => $user->email, 'password' => 'password']);

        $this->assertGuest();
    }

    public function test_un_visiteur_est_redirige_vers_la_connexion(): void
    {
        $this->get('/tableau-de-bord')->assertRedirect(route('login'));
    }

    public function test_deconnexion(): void
    {
        $user = $this->creerUser(Role::ParentEleve);

        $this->actingAs($user)->post('/deconnexion')->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_le_tableau_de_bord_affiche_le_role(): void
    {
        $user = $this->creerUser(Role::Directeur, Etablissement::factory()->create());

        $this->actingAs($user)->get('/tableau-de-bord')
            ->assertOk()
            ->assertSee('Directeur');
    }

    public function test_l_admin_general_accede_a_l_administration(): void
    {
        $this->actingAs($this->creerUser(Role::AdminGeneral))
            ->get('/administration')->assertOk();
    }

    public function test_un_directeur_ne_peut_pas_acceder_a_l_administration(): void
    {
        $this->actingAs($this->creerUser(Role::Directeur, Etablissement::factory()->create()))
            ->get('/administration')->assertForbidden();
    }

    public function test_isolation_des_donnees_entre_etablissements(): void
    {
        $a = Etablissement::factory()->create();
        $b = Etablissement::factory()->create();

        $directeurA = $this->creerUser(Role::Directeur, $a);
        $this->creerUser(Role::Enseignant, $a);
        $this->creerUser(Role::Enseignant, $b);
        $admin = $this->creerUser(Role::AdminGeneral);

        // Le directeur de A ne voit que les 2 utilisateurs de A
        $this->actingAs($directeurA);
        $this->assertCount(2, User::all());

        // L'admin général voit les 4
        $this->actingAs($admin);
        $this->assertCount(4, User::all());
    }

    public function test_un_nouvel_enregistrement_prend_l_etablissement_de_l_utilisateur_connecte(): void
    {
        $a = Etablissement::factory()->create();
        $directeurA = $this->creerUser(Role::Directeur, $a);

        $this->actingAs($directeurA);
        $nouveau = User::factory()->create(['etablissement_id' => null, 'role' => Role::Enseignant]);

        $this->assertEquals($a->id, $nouveau->etablissement_id);
    }
}