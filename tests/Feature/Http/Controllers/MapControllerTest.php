<?php

namespace Tests\Feature\Http\Controllers;

use App\Enums\PickupStatus;
use App\Enums\ReportStatus;
use App\Models\PickupRequest;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MapControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_map_shows_open_reports_and_pickup_requests_only(): void
    {
        Report::factory()->create(['description' => 'Dépôt à nettoyer']);
        Report::factory()->takenBy()->create(['description' => 'Dépôt pris en charge']);
        Report::factory()->create(['description' => 'Dépôt nettoyé', 'status' => ReportStatus::Nettoye]);
        Report::factory()->create(['description' => 'Dépôt rejeté', 'status' => ReportStatus::Rejete]);
        PickupRequest::factory()->create(['description' => 'Canapé à ramasser']);
        PickupRequest::factory()->create(['description' => 'Collecte faite', 'status' => PickupStatus::Effectuee]);

        $this->actingAs(User::factory()->create())
            ->get(route('map'))
            ->assertOk()
            ->assertViewHas('points', fn ($points): bool => $points->pluck('description')->sort()->values()->all()
                === ['Canapé à ramasser', 'Dépôt pris en charge', 'Dépôt à nettoyer']);
    }

    public function test_cleaned_report_stays_on_the_map_for_24_hours(): void
    {
        Report::factory()->create(['description' => 'Nettoyé ce matin', 'status' => ReportStatus::Nettoye, 'cleaned_at' => now()->subHours(3)]);
        Report::factory()->create(['description' => 'Nettoyé avant-hier', 'status' => ReportStatus::Nettoye, 'cleaned_at' => now()->subHours(25)]);

        $this->actingAs(User::factory()->create())
            ->get(route('map'))
            ->assertViewHas('points', fn ($points): bool => $points->pluck('description')->all() === ['Nettoyé ce matin']);
    }

    public function test_taken_report_exposes_the_volunteer_and_planned_date(): void
    {
        $report = Report::factory()->takenBy(User::factory()->create(['name' => 'Anrifa Saïd']))->create();

        $this->actingAs(User::factory()->create())
            ->get(route('map'))
            ->assertViewHas('points', fn ($points): bool => $points->first()['taker'] === 'Anrifa S.'
                && $points->first()['planned'] === $report->cleanupLabel());
    }
}
