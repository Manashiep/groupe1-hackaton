<?php

namespace Tests\Feature\Http\Controllers;

use App\Enums\PickupStatus;
use App\Models\PickupRequest;
use App\Models\User;
use App\Models\Village;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PickupRequestControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_request_is_saved_as_pending_with_its_village(): void
    {
        $citizen = User::factory()->create();
        $village = Village::factory()->create(['latitude' => -11.7038, 'longitude' => 43.2520]);

        $this->actingAs($citizen)
            ->post(route('pickups.store'), [
                'description' => 'Un vieux canapé et deux matelas',
                'latitude' => -11.7040,
                'longitude' => 43.2522,
                'preferred_date' => today()->addDays(2)->toDateString(),
            ])
            ->assertRedirect(route('pickups.index'));

        $pickup = PickupRequest::sole();
        $this->assertTrue($pickup->user->is($citizen));
        $this->assertTrue($pickup->village->is($village));
        $this->assertSame(PickupStatus::EnAttente, $pickup->status);
    }

    public function test_description_and_location_are_required(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('pickups.store'), [])
            ->assertSessionHasErrors(['description', 'latitude', 'longitude']);
    }

    public function test_agents_can_also_request_a_pickup(): void
    {
        $this->actingAs(User::factory()->agent()->create())
            ->get(route('pickups.create'))
            ->assertOk();
    }
}
