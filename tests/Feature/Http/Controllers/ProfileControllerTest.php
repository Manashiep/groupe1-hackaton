<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Association;
use App\Models\Due;
use App\Models\Report;
use App\Models\TruckPassage;
use App\Models\User;
use App\Models\Village;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_lists_engagements_reports_and_led_association(): void
    {
        $citizen = User::factory()->create();
        Report::factory()->takenBy($citizen)->create(['description' => 'Ravine à nettoyer']);
        Report::factory()->for($citizen)->create(['description' => 'Mon signalement']);
        Association::factory()->active()->for($citizen, 'leader')->create(['name' => 'Moroni Propre']);

        $this->actingAs($citizen)
            ->get(route('profile'))
            ->assertOk()
            ->assertSee('Ravine à nettoyer')
            ->assertSee('Mon signalement')
            ->assertSee('Gérer Moroni Propre');
    }

    public function test_citizen_sets_home_village_and_sees_dues(): void
    {
        $citizen = User::factory()->create();
        $village = Village::factory()->create(['name' => 'Iconi']);

        $this->actingAs($citizen)
            ->patch(route('profile.home-village'), ['home_village_id' => $village->id])
            ->assertSessionHas('success');
        $this->assertTrue($citizen->fresh()->homeVillage->is($village));

        $due = Due::factory()->for($citizen)->for(TruckPassage::factory()->for($village), 'passage')->create();

        $this->actingAs($citizen)
            ->get(route('profile'))
            ->assertSee('300 KMF · Iconi')
            ->assertSee(route('dues.pay', $due));
    }

    public function test_home_village_must_exist(): void
    {
        $this->actingAs(User::factory()->create())
            ->patch(route('profile.home-village'), ['home_village_id' => 999])
            ->assertSessionHasErrors('home_village_id');
    }
}
