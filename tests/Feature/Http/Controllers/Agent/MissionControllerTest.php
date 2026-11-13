<?php

namespace Tests\Feature\Http\Controllers\Agent;

use App\Enums\PickupStatus;
use App\Enums\ReportStatus;
use App\Models\PickupRequest;
use App\Models\Report;
use App\Models\Truck;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MissionControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_agent_starts_then_finishes_a_cleanup(): void
    {
        $truck = Truck::factory()->create();
        $report = Report::factory()->assigned($truck)->create();
        $agent = User::factory()->agent($truck)->create();

        $this->actingAs($agent)->post(route('agent.reports.start', $report));
        $this->assertSame(ReportStatus::EnCours, $report->fresh()->status);

        $this->actingAs($agent)->post(route('agent.reports.finish', $report))->assertSessionHas('success');
        $report->refresh();
        $this->assertSame(ReportStatus::Nettoye, $report->status);
        $this->assertNotNull($report->cleaned_at);
    }

    public function test_agent_cannot_close_a_report_assigned_to_another_truck(): void
    {
        $report = Report::factory()->assigned()->create();

        $this->actingAs(User::factory()->agent()->create())
            ->post(route('agent.reports.finish', $report))
            ->assertForbidden();

        $this->assertSame(ReportStatus::PrisEnCharge, $report->fresh()->status);
    }

    public function test_agent_marks_a_pickup_as_done(): void
    {
        $truck = Truck::factory()->create();
        $pickup = PickupRequest::factory()->create([
            'status' => PickupStatus::Acceptee, 'truck_id' => $truck->id, 'appointment_at' => now(),
        ]);

        $this->actingAs(User::factory()->agent($truck)->create())
            ->post(route('agent.pickups.finish', $pickup));

        $this->assertSame(PickupStatus::Effectuee, $pickup->fresh()->status);
    }
}
