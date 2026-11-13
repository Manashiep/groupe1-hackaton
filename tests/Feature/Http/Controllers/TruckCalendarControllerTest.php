<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Truck;
use App\Models\TruckNotice;
use App\Models\TruckSchedule;
use App\Models\User;
use App\Models\Village;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TruckCalendarControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_shows_only_the_slots_of_the_selected_village(): void
    {
        $iconi = Village::factory()->create(['name' => 'Iconi']);
        $mde = Village::factory()->create(['name' => 'Mdé']);
        $truck = Truck::factory()->create(['name' => 'Camion Sud']);
        $truck->villages()->attach([$iconi->id, $mde->id]);
        TruckSchedule::factory()->for($truck)->for($iconi)->create(['day_of_week' => 3, 'start_time' => '07:00', 'end_time' => '09:30']);
        TruckSchedule::factory()->for($truck)->for($mde)->create(['day_of_week' => 4, 'start_time' => '06:15', 'end_time' => '07:00']);

        $this->actingAs(User::factory()->create())
            ->get(route('trucks.index', ['village' => $iconi->slug]))
            ->assertOk()
            ->assertSee('Camion Sud')
            ->assertSee('07:00 – 09:30')
            ->assertDontSee('06:15');
    }

    public function test_next_passage_skips_todays_slot_once_it_is_over(): void
    {
        $this->travelTo(Carbon::parse('2026-11-11 12:00')); // mercredi midi
        $village = Village::factory()->create();
        $truck = Truck::factory()->create();
        $truck->villages()->attach($village);
        TruckSchedule::factory()->for($truck)->for($village)->create(['day_of_week' => 3, 'start_time' => '07:00', 'end_time' => '09:00']);
        TruckSchedule::factory()->for($truck)->for($village)->create(['day_of_week' => 5, 'start_time' => '07:00', 'end_time' => '09:00']);

        $this->actingAs(User::factory()->create())
            ->get(route('trucks.index', ['village' => $village->slug]))
            ->assertSeeText('Vendredi 13 novembre')
            ->assertDontSeeText('Aujourd\'hui');
    }

    public function test_shows_recent_alerts_from_the_villages_trucks(): void
    {
        $village = Village::factory()->create();
        $truck = Truck::factory()->create();
        $truck->villages()->attach($village);
        TruckNotice::factory()->for($truck)->create(['message' => 'On repasse demain à 7h']);
        TruckNotice::factory()->for($truck)->create(['message' => 'Vieille alerte', 'created_at' => now()->subDays(2)]);

        $this->actingAs(User::factory()->create())
            ->get(route('trucks.index', ['village' => $village->slug]))
            ->assertSee('On repasse demain à 7h')
            ->assertDontSee('Vieille alerte');
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
