<?php

namespace Tests\Feature\Filament\Resources\PickupRequests;

use App\Enums\PickupStatus;
use App\Filament\Resources\PickupRequests\Pages\ManagePickupRequests;
use App\Models\PickupRequest;
use App\Models\Truck;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PickupRequestResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_accepting_a_request_sets_the_appointment_and_truck(): void
    {
        $pickup = PickupRequest::factory()->create();
        $truck = Truck::factory()->create();
        $appointment = today()->addDays(2)->setTime(10, 0);
        $this->actingAs(User::factory()->responsable()->create());

        Livewire::test(ManagePickupRequests::class)
            ->callAction(TestAction::make('accept')->table($pickup), data: [
                'truck_id' => $truck->id,
                'appointment_at' => $appointment,
            ])
            ->assertHasNoFormErrors();

        $pickup->refresh();
        $this->assertSame(PickupStatus::Acceptee, $pickup->status);
        $this->assertTrue($pickup->truck->is($truck));
        $this->assertTrue($pickup->appointment_at->equalTo($appointment));
    }
}
