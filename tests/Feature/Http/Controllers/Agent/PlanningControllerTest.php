<?php

namespace Tests\Feature\Http\Controllers\Agent;

use App\Models\Report;
use App\Models\Truck;
use App\Models\TruckSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanningControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_lists_route_days_and_missions_for_the_next_seven_days(): void
    {
        $truck = Truck::factory()->create();
        $tomorrow = today()->addDay();
        TruckSchedule::factory()->for($truck)->create(['day_of_week' => $tomorrow->dayOfWeekIso, 'start_time' => '06:30', 'end_time' => '08:00']);
        Report::factory()->assigned($truck)->create(['cleanup_at' => $tomorrow->copy()->setTime(15, 0)]);

        $this->actingAs(User::factory()->agent($truck)->create())
            ->get(route('agent.planning'))
            ->assertOk()
            ->assertViewHas('days', fn ($days): bool => $days[1]['schedule']?->start_time === '06:30:00'
                && $days[1]['missions']->count() === 1
                && $days[0]['missions']->isEmpty());
    }
}
