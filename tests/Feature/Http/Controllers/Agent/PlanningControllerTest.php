<?php

namespace Tests\Feature\Http\Controllers\Agent;

use App\Models\Truck;
use App\Models\TruckSchedule;
use App\Models\User;
use App\Models\Village;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanningControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_lists_each_days_villages_for_the_next_seven_days(): void
    {
        $truck = Truck::factory()->create();
        $tomorrow = today()->addDay();
        TruckSchedule::factory()->for($truck)->for(Village::factory()->create(['name' => 'Itsandra']))
            ->create(['day_of_week' => $tomorrow->dayOfWeekIso, 'start_time' => '06:30', 'end_time' => '08:00']);

        $this->actingAs(User::factory()->agent($truck)->create())
            ->get(route('agent.planning'))
            ->assertOk()
            ->assertSee('Itsandra')
            ->assertViewHas('days', fn ($days): bool => $days->count() === 7
                && $days[1]['slots']->count() === 1
                && $days[0]['slots']->isEmpty());
    }
}
