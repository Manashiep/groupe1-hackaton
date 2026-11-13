<?php

namespace Tests\Feature\Http\Controllers;

use App\Enums\ComplaintReason;
use App\Models\Truck;
use App\Models\TruckComplaint;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TruckComplaintControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_citizen_reports_a_problem_with_a_truck(): void
    {
        $citizen = User::factory()->create();
        $truck = Truck::factory()->create();

        $this->actingAs($citizen)
            ->from(route('trucks.index'))
            ->post(route('trucks.complaints.store', $truck), ['reason' => 'comportement', 'message' => 'Sacs tombés du camion'])
            ->assertRedirect(route('trucks.index'))
            ->assertSessionHas('success');

        $complaint = TruckComplaint::sole();
        $this->assertTrue($complaint->truck->is($truck));
        $this->assertTrue($complaint->user->is($citizen));
        $this->assertSame(ComplaintReason::Comportement, $complaint->reason);
        $this->assertNull($complaint->handled_at);
    }

    public function test_unknown_reason_is_rejected(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('trucks.complaints.store', Truck::factory()->create()), ['reason' => 'inconnu'])
            ->assertSessionHasErrors('reason');

        $this->assertDatabaseCount('truck_complaints', 0);
    }
}
