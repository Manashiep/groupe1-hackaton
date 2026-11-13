<?php

namespace Tests\Feature\Http\Controllers\Agent;

use App\Models\RouteStop;
use App\Models\Truck;
use App\Models\TruckSchedule;
use App\Models\User;
use App\Models\Village;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RouteControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_shows_todays_villages_and_the_route_stops(): void
    {
        $truck = Truck::factory()->create();
        $village = Village::factory()->create(['name' => 'Hadouja']);
        TruckSchedule::factory()->for($truck)->for($village)->create(['day_of_week' => today()->dayOfWeekIso, 'start_time' => '08:00', 'end_time' => '09:30']);
        RouteStop::factory()->for($truck)->create(['position' => 1, 'label' => 'Place du marché']);

        $this->actingAs(User::factory()->agent($truck)->create())
            ->get(route('agent.route'))
            ->assertOk()
            ->assertSee('Hadouja')
            ->assertSee('08:00 – 09:30')
            ->assertSee('Place du marché');
    }

    public function test_agent_ticks_then_unticks_a_stop_for_today(): void
    {
        $truck = Truck::factory()->create();
        $stop = RouteStop::factory()->for($truck)->create();
        $agent = User::factory()->agent($truck)->create();

        $this->actingAs($agent)->post(route('agent.stops.toggle', $stop))->assertRedirect();
        $this->assertTrue($stop->visits()->whereDate('visit_date', today())->exists());

        $this->actingAs($agent)->post(route('agent.stops.toggle', $stop));
        $this->assertFalse($stop->visits()->exists());
    }

    public function test_agent_cannot_tick_a_stop_of_another_truck(): void
    {
        $stop = RouteStop::factory()->create();

        $this->actingAs(User::factory()->agent()->create())
            ->post(route('agent.stops.toggle', $stop))
            ->assertForbidden();

        $this->assertFalse($stop->visits()->exists());
    }

    public function test_citizens_cannot_open_the_route(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('agent.route'))
            ->assertForbidden();
    }
}
