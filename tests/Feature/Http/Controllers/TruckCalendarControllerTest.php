<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Truck;
use App\Models\TruckSchedule;
use App\Models\User;
use App\Models\Village;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TruckCalendarControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_shows_the_schedule_of_the_selected_villages_truck(): void
    {
        $village = Village::factory()->create(['name' => 'Iconi']);
        $truck = Truck::factory()->for($village)->create(['name' => 'Camion Iconi']);
        TruckSchedule::factory()->for($truck)->create(['day_of_week' => 3, 'start_time' => '07:00', 'end_time' => '09:30']);

        $this->actingAs(User::factory()->create())
            ->get(route('trucks.index', ['village' => $village->slug]))
            ->assertOk()
            ->assertSee('Camion Iconi')
            ->assertSee('Mercredi')
            ->assertSee('07:00 – 09:30');
    }

    public function test_next_passage_skips_todays_slot_once_it_is_over(): void
    {
        $this->travelTo(Carbon::parse('2026-11-11 12:00')); // mercredi midi
        $village = Village::factory()->create();
        $truck = Truck::factory()->for($village)->create();
        TruckSchedule::factory()->for($truck)->create(['day_of_week' => 3, 'start_time' => '07:00', 'end_time' => '09:00']);
        TruckSchedule::factory()->for($truck)->create(['day_of_week' => 5, 'start_time' => '07:00', 'end_time' => '09:00']);

        $this->actingAs(User::factory()->create())
            ->get(route('trucks.index', ['village' => $village->slug]))
            ->assertSeeText('Vendredi 13 novembre')
            ->assertDontSeeText('Aujourd\'hui');
    }

    public function test_remembers_the_last_village_viewed(): void
    {
        $citizen = User::factory()->create();
        $village = Village::factory()->create();

        $this->actingAs($citizen)->get(route('trucks.index', ['village' => $village->slug]));

        $this->assertTrue($citizen->fresh()->village->is($village));
    }

    public function test_village_without_truck_says_so(): void
    {
        $village = Village::factory()->create(['name' => 'Vouvouni']);

        $this->actingAs(User::factory()->create())
            ->get(route('trucks.index', ['village' => $village->slug]))
            ->assertSeeText('Aucun camion n\'est encore affecté à Vouvouni');
    }

    public function test_agents_do_not_get_the_calendar(): void
    {
        $this->actingAs(User::factory()->agent()->create())
            ->get(route('trucks.index'))
            ->assertForbidden();
    }
}
