<?php

namespace Tests\Feature\Http\Controllers;

use App\Enums\ReportStatus;
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

        $this->actingAs($citizen)
            ->post(route('reports.store'), [
                'photo' => UploadedFile::fake()->image('depot.jpg'),
                'description' => 'Tas d\'ordures derrière l\'école',
                'latitude' => -11.7041,
                'longitude' => 43.2525,
            ])
            ->assertRedirect(route('reports.index'));

        $report = Report::sole();
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

    public function test_index_lists_only_the_users_own_reports(): void
    {
        $citizen = User::factory()->create();
        Report::factory()->for($citizen)->create(['description' => 'Mon signalement']);
        Report::factory()->create(['description' => 'Signalement d\'un voisin']);

        $this->actingAs($citizen)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertSee('Mon signalement')
            ->assertDontSee('Signalement d\'un voisin');
    }

    public function test_index_shows_the_planned_cleanup_of_an_assigned_report(): void
    {
        $citizen = User::factory()->create();
        Report::factory()->for($citizen)->assigned()->create();

        $this->actingAs($citizen)
            ->get(route('reports.index'))
            ->assertSee('Pris en charge')
            ->assertSee('Nettoyage prévu');
    }
}
