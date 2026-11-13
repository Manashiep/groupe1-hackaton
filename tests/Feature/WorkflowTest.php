<?php

namespace Tests\Feature;

use App\Enums\CollectionStatus;
use App\Enums\InterventionStatus;
use App\Enums\PickupStatus;
use App\Enums\ReportStatus;
use App\Models\Collection;
use App\Models\PickupRequest;
use App\Models\Quartier;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Parcours complets : signalement → vérification → intervention → nettoyage, collectes, demandes.
 */
class WorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function user(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    public function test_report_lifecycle_from_citizen_to_clean_zone(): void
    {
        Storage::fake('public');
        $citizen = $this->user('citoyen@cleancomores.km');
        $staff = $this->user('responsable@cleancomores.km');
        $quartier = Quartier::where('slug', 'badjanani')->first();
        $pointsBefore = $citizen->points;

        // 1. Le citoyen signale avec photo et position.
        $this->actingAs($citizen)->post('/espace/signalements', [
            'category' => 'depot_sauvage',
            'description' => 'Gros tas de sacs devant le terrain de foot.',
            'photo' => UploadedFile::fake()->image('depot.jpg', 800, 600),
            'latitude' => -11.6987,
            'longitude' => 43.2559,
            'quartier_id' => $quartier->id,
        ])->assertRedirect();

        $report = Report::latest('id')->first();
        $this->assertSame(ReportStatus::Nouveau, $report->status);
        Storage::disk('public')->assertExists($report->photo_path);
        $this->assertTrue($staff->notifications()->where('data->title', 'Nouveau signalement')->exists());

        // 2. Le responsable vérifie : +10 points, une seule fois.
        $this->actingAs($staff)->patch("/gestion/signalements/{$report->id}/statut", ['action' => 'valider'])->assertRedirect();
        $this->actingAs($staff)->patch("/gestion/signalements/{$report->id}/statut", ['action' => 'valider']);
        $this->assertSame($pointsBefore + 10, $citizen->fresh()->points);

        // 3. Il affecte une intervention à l'équipe Nord.
        $team = $this->user('agent.nord@cleancomores.km')->team;
        $this->actingAs($staff)->post("/gestion/signalements/{$report->id}/intervention", [
            'team_id' => $team->id, 'scheduled_at' => now()->addHour()->format('Y-m-d H:i'), 'priority' => 'haute',
        ])->assertRedirect();
        $intervention = $report->interventions()->first();
        $this->assertSame(ReportStatus::Planifie, $report->fresh()->status);

        // 4. Un agent d'une autre équipe ne peut pas y toucher.
        $this->actingAs($this->user('agent.sud@cleancomores.km'))
            ->post("/agent/interventions/{$intervention->id}/demarrer")->assertForbidden();

        // 5. L'agent Nord démarre puis termine avec une photo « après ».
        $agent = $this->user('agent.nord@cleancomores.km');
        $this->actingAs($agent)->post("/agent/interventions/{$intervention->id}/demarrer")->assertRedirect();
        $this->assertSame(ReportStatus::EnCours, $report->fresh()->status);

        $this->actingAs($agent)->post("/agent/interventions/{$intervention->id}/terminer", [
            'completion_note' => '8 sacs évacués.',
            'completion_photo' => UploadedFile::fake()->image('apres.jpg'),
        ])->assertRedirect();

        $report->refresh();
        $this->assertSame(ReportStatus::Resolu, $report->status);
        $this->assertNotNull($report->resolved_at);
        $this->assertSame(InterventionStatus::Terminee, $intervention->fresh()->status);
        $this->assertGreaterThanOrEqual(5, $report->events()->count());
        $this->assertTrue($citizen->notifications()->where('data->title', 'Zone nettoyée')->exists());

        $this->actingAs($citizen)->get("/espace/signalements/{$report->id}")->assertOk()->assertSee('Zone nettoyée');
    }

    public function test_report_requires_a_location_in_the_comoros(): void
    {
        $citizen = $this->user('citoyen@cleancomores.km');

        $this->actingAs($citizen)->post('/espace/signalements', [
            'category' => 'depot_sauvage', 'description' => 'Des déchets partout ici.',
            'latitude' => 48.85, 'longitude' => 2.35, 'quartier_id' => $citizen->quartier_id,
        ])->assertSessionHasErrors(['latitude', 'longitude']);
    }

    public function test_agent_runs_a_collection_and_residents_are_notified(): void
    {
        $collection = Collection::where('status', CollectionStatus::Programmee)
            ->whereDate('scheduled_date', '>=', today())->whereNotNull('team_id')->first();
        $agent = User::where('role', 'agent')->where('team_id', $collection->team_id)->first();
        $resident = User::create([
            'name' => 'Voisin Test', 'email' => 'voisin@exemple.km', 'password' => 'password',
            'role' => 'citoyen', 'quartier_id' => $collection->quartier_id,
        ]);

        $this->actingAs($agent)->post("/agent/collectes/{$collection->id}/demarrer")->assertRedirect();
        $this->assertSame(CollectionStatus::EnCours, $collection->fresh()->status);
        $this->assertTrue($resident->notifications()->where('data->title', 'La camionnette arrive')->exists());

        $this->actingAs($agent)->post("/agent/collectes/{$collection->id}/terminer")->assertRedirect();
        $collection->refresh();
        $this->assertSame(CollectionStatus::Effectuee, $collection->status);
        $this->assertSame($agent->id, $collection->confirmed_by);
    }

    public function test_citizen_can_report_a_missed_collection_once_it_is_late(): void
    {
        $citizen = $this->user('citoyen@cleancomores.km');
        $late = Collection::create([
            'quartier_id' => $citizen->quartier_id, 'scheduled_date' => today()->subDay(), 'start_time' => '07:00',
            'waste_type' => 'menagers', 'status' => CollectionStatus::Programmee,
        ]);
        $future = Collection::create([
            'quartier_id' => $citizen->quartier_id, 'scheduled_date' => today()->addDays(2), 'start_time' => '07:00',
            'waste_type' => 'menagers', 'status' => CollectionStatus::Programmee,
        ]);

        $this->actingAs($citizen)->post("/espace/collectes/{$late->id}/non-effectuee")->assertSessionHas('success');
        $this->actingAs($citizen)->post("/espace/collectes/{$late->id}/non-effectuee");
        $this->assertSame(1, $late->complaints()->count());

        $this->actingAs($citizen)->post("/espace/collectes/{$future->id}/non-effectuee")->assertSessionHas('error');
        $this->assertSame(0, $future->complaints()->count());
    }

    public function test_staff_reschedules_a_missed_collection(): void
    {
        $staff = $this->user('responsable@cleancomores.km');
        $missed = Collection::where('status', CollectionStatus::NonEffectuee)->first();

        $this->actingAs($staff)->post("/gestion/collectes/{$missed->id}/rattrapage", [
            'date' => today()->addDay()->toDateString(), 'start_time' => '15:00',
        ])->assertSessionHas('success');

        $this->assertTrue(Collection::where('quartier_id', $missed->quartier_id)
            ->whereDate('scheduled_date', today()->addDay())->where('start_time', '15:00:00')->exists());
    }

    public function test_pickup_request_is_accepted_with_an_appointment(): void
    {
        $citizen = $this->user('citoyen@cleancomores.km');
        $staff = $this->user('responsable@cleancomores.km');

        $this->actingAs($citizen)->post('/espace/demandes', [
            'requester_type' => 'particulier', 'waste_type' => 'encombrants', 'volume' => 'moyen',
            'description' => 'Un vieux frigo et une gazinière.', 'quartier_id' => $citizen->quartier_id,
            'address' => 'Maison verte près de la place',
        ])->assertRedirect('/espace/demandes');

        $pickup = PickupRequest::latest('id')->first();
        $this->actingAs($staff)->post("/gestion/demandes/{$pickup->id}/accepter", [
            'appointment_at' => now()->addDays(2)->setTime(9, 0)->format('Y-m-d H:i'),
            'team_id' => $this->user('agent.nord@cleancomores.km')->team_id,
        ])->assertSessionHas('success');

        $pickup->refresh();
        $this->assertSame(PickupStatus::Acceptee, $pickup->status);
        $this->assertNotNull($pickup->intervention);
        $this->assertTrue($citizen->notifications()->where('data->title', 'Rendez-vous fixé')->exists());
    }

    public function test_schedule_changes_regenerate_upcoming_collections(): void
    {
        $staff = $this->user('responsable@cleancomores.km');
        $quartier = Quartier::where('slug', 'iconi')->first();
        $day = today()->addDays(3);

        $this->actingAs($staff)->post('/gestion/calendrier', [
            'quartier_id' => $quartier->id, 'team_id' => $this->user('agent.sud@cleancomores.km')->team_id,
            'day_of_week' => $day->dayOfWeekIso, 'start_time' => '16:00', 'end_time' => '17:00', 'waste_type' => 'recyclables',
        ])->assertSessionHas('success');

        $this->assertTrue(Collection::where('quartier_id', $quartier->id)->whereDate('scheduled_date', $day)
            ->where('start_time', '16:00:00')->exists());
    }
}
