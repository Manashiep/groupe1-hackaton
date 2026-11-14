<?php

namespace Tests\Feature\Http\Controllers;

use App\Enums\PickupStatus;
use App\Models\PickupRequest;
use App\Models\User;
use App\Models\Village;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PickupRequestControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_request_is_published_with_its_village(): void
    {
        $citizen = User::factory()->create();
        $village = Village::factory()->create(['latitude' => -11.7038, 'longitude' => 43.2520]);

        $response = $this->actingAs($citizen)->post(route('pickups.store'), [
            'description' => 'Un vieux canapé et deux matelas',
            'latitude' => -11.7040,
            'longitude' => 43.2522,
            'preferred_date' => today()->addDays(2)->toDateString(),
        ]);

        $pickup = PickupRequest::sole();
        $response->assertRedirect(route('pickups.show', $pickup));
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

    public function test_volunteer_takes_the_request_then_marks_it_done(): void
    {
        $pickup = PickupRequest::factory()->create();
        $volunteer = User::factory()->agent()->create();

        $this->actingAs($volunteer)->post(route('pickups.take', $pickup), ['planned_at' => today()->addDay()->format('Y-m-d 10:00')]);
        $this->assertSame(PickupStatus::PriseEnCharge, $pickup->fresh()->status);

        Storage::fake('public');
        $this->actingAs($volunteer)->post(route('pickups.finish', $pickup), ['proof_photo' => UploadedFile::fake()->image('ramasse.jpg')]);
        $this->assertSame(PickupStatus::Effectuee, $pickup->fresh()->status);
        $this->assertSame(30, (int) $volunteer->pointAwards()->sum('points'));
    }

    public function test_marking_done_requires_a_proof_photo(): void
    {
        $volunteer = User::factory()->create();
        $pickup = PickupRequest::factory()->takenBy($volunteer)->create();

        $this->actingAs($volunteer)
            ->post(route('pickups.finish', $pickup))
            ->assertSessionHasErrors('proof_photo');

        $this->assertSame(PickupStatus::PriseEnCharge, $pickup->fresh()->status);
    }

    public function test_only_the_volunteer_can_mark_the_pickup_done(): void
    {
        $pickup = PickupRequest::factory()->takenBy()->create();

        $this->actingAs(User::factory()->create())
            ->post(route('pickups.finish', $pickup))
            ->assertForbidden();
    }
}
