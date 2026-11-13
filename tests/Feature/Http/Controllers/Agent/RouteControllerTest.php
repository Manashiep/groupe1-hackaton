<?php

namespace Tests\Feature\Http\Controllers\Agent;

use App\Models\Report;
use App\Models\RouteStop;
use App\Models\Truck;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RouteControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_shows_the_stops_and_todays_missions_of_the_agents_truck(): void
    {
        $truck = Truck::factory()->create();
        RouteStop::factory()->for($truck)->create(['position' => 1, 'label' => 'Place du marché']);
        Report::factory()->assigned($truck)->create(['cleanup_at' => today()->setTime(14, 0), 'description' => 'Dépôt à nettoyer']);
        Report::factory()->assigned($truck)->create(['cleanup_at' => today()->addDays(3), 'description' => 'Dépôt plus tard']);

        $this->actingAs(User::factory()->agent($truck)->create())
            ->get(route('agent.route'))
            ->assertOk()
            ->assertSee('Place du marché')
            ->assertSee('Dépôt à nettoyer')
            ->assertDontSee('Dépôt plus tard');
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
