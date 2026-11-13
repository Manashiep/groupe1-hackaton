<?php

namespace Tests\Feature;

use App\Models\Quartier;
use App\Models\Report;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Chaque page de chaque espace s'affiche, et les espaces sont cloisonnés par rôle.
 */
class PagesTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function user(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    public function test_public_pages_render(): void
    {
        $quartier = Quartier::first();

        foreach (['/', '/calendrier', '/calendrier?quartier='.$quartier->slug, '/carte', '/quartiers/'.$quartier->slug, '/connexion', '/inscription'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_citizen_pages_render_and_admin_is_forbidden(): void
    {
        $citizen = $this->user('citoyen@cleancomores.km');
        $report = $citizen->reports()->first();

        foreach (['/espace', '/espace/signalements', '/espace/signalements/nouveau', '/espace/signalements/'.$report->id, '/espace/demandes', '/espace/demandes/nouvelle', '/notifications'] as $url) {
            $this->actingAs($citizen)->get($url)->assertOk();
        }

        $this->actingAs($citizen)->get('/gestion')->assertForbidden();
        $this->actingAs($citizen)->get('/agent')->assertForbidden();
    }

    public function test_citizen_cannot_see_someone_elses_report(): void
    {
        $citizen = $this->user('citoyen@cleancomores.km');
        $other = Report::where('user_id', '!=', $citizen->id)->first();

        $this->actingAs($citizen)->get('/espace/signalements/'.$other->id)->assertForbidden();
    }

    public function test_agent_pages_render(): void
    {
        $agent = $this->user('agent.centre@cleancomores.km');

        foreach (['/agent', '/agent/planning', '/agent?date='.today()->addDay()->toDateString()] as $url) {
            $this->actingAs($agent)->get($url)->assertOk();
        }
        $this->actingAs($agent)->get('/gestion')->assertForbidden();
    }

    public function test_responsable_pages_render_but_not_user_management(): void
    {
        $staff = $this->user('responsable@cleancomores.km');
        $report = Report::first();
        $quartier = Quartier::first();
        $team = Team::first();

        foreach ([
            '/gestion', '/gestion/signalements', '/gestion/signalements?status=ouverts', '/gestion/signalements/'.$report->id,
            '/gestion/interventions', '/gestion/collectes', '/gestion/collectes?date='.today()->subDays(2)->toDateString(),
            '/gestion/itineraires', '/gestion/itineraires?team='.$team->id, '/gestion/calendrier', '/gestion/demandes',
            '/gestion/demandes?status=toutes', '/gestion/quartiers', '/gestion/quartiers/create',
            '/gestion/quartiers/'.$quartier->slug.'/edit', '/gestion/equipes', '/gestion/equipes/create',
            '/gestion/equipes/'.$team->id.'/edit', '/notifications',
        ] as $url) {
            $this->actingAs($staff)->get($url)->assertOk();
        }

        $this->actingAs($staff)->get('/gestion/utilisateurs')->assertForbidden();
    }

    public function test_admin_can_manage_users(): void
    {
        $admin = $this->user('admin@cleancomores.km');

        foreach (['/gestion/utilisateurs', '/gestion/utilisateurs?role=agent', '/gestion/utilisateurs/create', '/gestion/utilisateurs/'.$admin->id.'/edit'] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    public function test_login_redirects_each_role_to_its_space(): void
    {
        foreach ([
            'citoyen@cleancomores.km' => '/espace',
            'agent.nord@cleancomores.km' => '/agent',
            'responsable@cleancomores.km' => '/gestion',
        ] as $email => $home) {
            $this->post('/connexion', ['email' => $email, 'password' => 'password'])->assertRedirect(url($home));
            $this->post('/deconnexion');
        }
    }

    public function test_registration_creates_a_citizen_only(): void
    {
        $this->post('/inscription', [
            'name' => 'Nouvel Habitant', 'email' => 'nouveau@exemple.km', 'quartier_id' => Quartier::first()->id,
            'password' => 'motdepasse1', 'password_confirmation' => 'motdepasse1', 'role' => 'admin',
        ])->assertRedirect('/espace');

        $this->assertSame('citoyen', User::where('email', 'nouveau@exemple.km')->first()->role->value);
    }
}
