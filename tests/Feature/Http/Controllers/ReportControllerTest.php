<?php

namespace Tests\Feature\Http\Controllers;

use App\Enums\ReportStatus;
use App\Models\Association;
use App\Models\Report;
use App\Models\User;
use App\Models\Village;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReportControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('reports.create'))->assertRedirect(route('login'));
    }

    public function test_staff_cannot_use_the_citizen_report_form(): void
    {
        $this->actingAs(User::factory()->responsable()->create())
            ->get(route('reports.create'))
            ->assertForbidden();
    }

    public function test_valid_report_is_saved_with_photo_and_nearest_village(): void
    {
        Storage::fake('public');
        $citizen = User::factory()->create();
        $near = Village::factory()->create(['latitude' => -11.7038, 'longitude' => 43.2520]);
        Village::factory()->create(['latitude' => -11.7452, 'longitude' => 43.2378]);

        $response = $this->actingAs($citizen)->post(route('reports.store'), [
            'photo' => UploadedFile::fake()->image('depot.jpg'),
            'description' => 'Tas d\'ordures derrière l\'école',
            'latitude' => -11.7041,
            'longitude' => 43.2525,
        ]);

        $report = Report::sole();
        $response->assertRedirect(route('reports.show', $report));
        $this->assertTrue($report->user->is($citizen));
        $this->assertTrue($report->village->is($near));
        $this->assertSame(ReportStatus::Nouveau, $report->status);
        Storage::disk('public')->assertExists($report->photo_path);
    }

    public function test_report_without_a_location_is_rejected(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('reports.store'), ['description' => 'Déchets'])
            ->assertSessionHasErrors(['latitude' => 'Indiquez l\'endroit sur la carte.']);

        $this->assertDatabaseCount('reports', 0);
    }

    public function test_citizen_takes_charge_of_a_report_with_a_date(): void
    {
        $report = Report::factory()->create();
        $volunteer = User::factory()->create();
        $plannedAt = today()->addDays(2)->setTime(8, 30);

        $this->actingAs($volunteer)
            ->post(route('reports.take', $report), ['planned_at' => $plannedAt->format('Y-m-d H:i'), 'taker_note' => 'On vient à trois'])
            ->assertSessionHas('success');

        $report->refresh();
        $this->assertSame(ReportStatus::PrisEnCharge, $report->status);
        $this->assertTrue($report->taker->is($volunteer));
        $this->assertTrue($report->cleanup_at->equalTo($plannedAt));
    }

    public function test_leader_can_take_charge_in_the_name_of_their_association(): void
    {
        $leader = User::factory()->create();
        $association = Association::factory()->active()->for($leader, 'leader')->create();
        $report = Report::factory()->create();

        $this->actingAs($leader)->post(route('reports.take', $report), [
            'planned_at' => today()->addDay()->format('Y-m-d 08:00'),
            'association_id' => $association->id,
        ]);

        $this->assertSame($association->name, $report->fresh()->takerName());
    }

    public function test_cannot_take_charge_in_the_name_of_someone_elses_association(): void
    {
        $association = Association::factory()->active()->create();
        $report = Report::factory()->create();

        $this->actingAs(User::factory()->create())
            ->post(route('reports.take', $report), ['planned_at' => today()->addDay()->format('Y-m-d 08:00'), 'association_id' => $association->id])
            ->assertSessionHasErrors('association_id');

        $this->assertSame(ReportStatus::Nouveau, $report->fresh()->status);
    }

    public function test_a_report_already_taken_cannot_be_taken_again(): void
    {
        $first = User::factory()->create();
        $report = Report::factory()->takenBy($first)->create();

        $this->actingAs(User::factory()->create())
            ->post(route('reports.take', $report), ['planned_at' => today()->addDay()->format('Y-m-d 08:00')])
            ->assertSessionHasErrors('planned_at');

        $this->assertTrue($report->fresh()->taker->is($first));
    }

    public function test_taker_postpones_with_a_note_then_finishes(): void
    {
        $taker = User::factory()->create();
        $report = Report::factory()->takenBy($taker)->create();
        $newDate = today()->addDays(3)->setTime(9, 0);

        $this->actingAs($taker)->post(route('reports.postpone', $report), [
            'planned_at' => $newDate->format('Y-m-d H:i'),
            'taker_note' => 'Il reste les gravats',
        ]);
        $report->refresh();
        $this->assertTrue($report->cleanup_at->equalTo($newDate));
        $this->assertSame('Il reste les gravats', $report->taker_note);

        $this->actingAs($taker)->post(route('reports.finish', $report));
        $report->refresh();
        $this->assertSame(ReportStatus::Nettoye, $report->status);
        $this->assertNotNull($report->cleaned_at);
    }

    public function test_only_the_taker_can_finish_a_report(): void
    {
        $report = Report::factory()->takenBy()->create();

        $this->actingAs(User::factory()->create())
            ->post(route('reports.finish', $report))
            ->assertForbidden();

        $this->assertSame(ReportStatus::PrisEnCharge, $report->fresh()->status);
    }

    public function test_releasing_makes_the_report_available_again(): void
    {
        $taker = User::factory()->create();
        $report = Report::factory()->takenBy($taker)->create();

        $this->actingAs($taker)->post(route('reports.release', $report));

        $report->refresh();
        $this->assertSame(ReportStatus::Nouveau, $report->status);
        $this->assertNull($report->taken_by_id);
        $this->assertNull($report->cleanup_at);
    }

    public function test_show_displays_who_takes_care_of_it_and_when(): void
    {
        $report = Report::factory()->takenBy(User::factory()->create(['name' => 'Ibrahim Mzé']))->create();

        $this->actingAs(User::factory()->create())
            ->get(route('reports.show', $report))
            ->assertOk()
            ->assertSee('Ibrahim M.')
            ->assertSee('Nettoyage prévu');
    }

    public function test_reporting_is_limited_to_five_per_minute(): void
    {
        $citizen = User::factory()->create();

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->actingAs($citizen)->post(route('reports.store'))->assertRedirect();
        }

        $this->actingAs($citizen)->post(route('reports.store'))->assertTooManyRequests();
    }
}
