<?php

namespace Tests\Feature\Http\Controllers;

use App\Enums\ReportStatus;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MapControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_map_shows_only_reports_still_to_clean(): void
    {
        Report::factory()->create(['description' => 'Dépôt nouveau']);
        Report::factory()->assigned()->create(['description' => 'Dépôt planifié']);
        Report::factory()->create(['description' => 'Dépôt nettoyé', 'status' => ReportStatus::Nettoye]);
        Report::factory()->create(['description' => 'Dépôt rejeté', 'status' => ReportStatus::Rejete]);

        $this->actingAs(User::factory()->create())
            ->get(route('map'))
            ->assertOk()
            ->assertViewHas('points', fn ($points): bool => $points->pluck('description')->sort()->values()->all() === ['Dépôt nouveau', 'Dépôt planifié']);
    }

    public function test_assigned_report_exposes_the_truck_and_cleanup_time(): void
    {
        $report = Report::factory()->assigned()->create();

        $this->actingAs(User::factory()->create())
            ->get(route('map'))
            ->assertViewHas('points', fn ($points): bool => $points->first()['truck'] === $report->truck->name
                && $points->first()['cleanup'] === $report->cleanupLabel());
    }
}
