<?php

namespace Tests\Feature\Filament\Widgets;

use App\Enums\ReportStatus;
use App\Filament\Widgets\ActivityChart;
use App\Filament\Widgets\OpenTruckComplaints;
use App\Filament\Widgets\ReportsMap;
use App\Filament\Widgets\StatsOverview;
use App\Filament\Widgets\UnclaimedReports;
use App\Models\Report;
use App\Models\TruckComplaint;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardWidgetsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->responsable()->create());
    }

    public function test_stats_count_reports_still_without_a_volunteer(): void
    {
        Report::factory()->count(2)->create();
        Report::factory()->takenBy()->create();

        Livewire::test(StatsOverview::class)
            ->assertSee('Signalements (7 jours)')
            ->assertSee('2 sans volontaire');
    }

    public function test_map_shows_what_residents_see_with_links_to_the_mairie_space(): void
    {
        $open = Report::factory()->create(['description' => 'Tas près du marché']);
        Report::factory()->create(['description' => 'Dépôt rejeté', 'status' => ReportStatus::Rejete]);

        Livewire::test(ReportsMap::class)
            ->assertSee('Carte des signalements')
            ->assertSee('1 point(s)');

        $points = (new ReportsMap)->getPoints();
        $this->assertSame(['Tas près du marché'], array_column($points, 'description'));
        $this->assertStringContainsString('/admin/', $points[0]['url']);
        $this->assertStringContainsString('tableActionRecord='.$open->id, $points[0]['url']);
    }

    public function test_unclaimed_reports_lists_only_reports_without_a_volunteer(): void
    {
        $unclaimed = Report::factory()->create();
        $taken = Report::factory()->takenBy()->create();

        Livewire::test(UnclaimedReports::class)
            ->assertCanSeeTableRecords([$unclaimed])
            ->assertCanNotSeeTableRecords([$taken]);
    }

    public function test_marking_a_truck_complaint_as_handled_removes_it_from_the_list(): void
    {
        $complaint = TruckComplaint::factory()->create();

        Livewire::test(OpenTruckComplaints::class)
            ->callAction(TestAction::make('handle')->table($complaint))
            ->assertCanNotSeeTableRecords([$complaint]);

        $this->assertNotNull($complaint->fresh()->handled_at);
    }

    public function test_activity_chart_renders_with_reports_and_cleanups(): void
    {
        Report::factory()->create();
        Report::factory()->create(['status' => ReportStatus::Nettoye, 'cleaned_at' => now()]);

        Livewire::test(ActivityChart::class)
            ->assertOk()
            ->assertSee('Signalements et nettoyages');
    }
}
