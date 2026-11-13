<?php

namespace Tests\Feature\Filament\Resources\PickupRequests;

use App\Enums\PickupStatus;
use App\Filament\Resources\PickupRequests\Pages\ManagePickupRequests;
use App\Models\PickupRequest;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PickupRequestResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_responsable_refuses_a_request_with_a_reason(): void
    {
        $pickup = PickupRequest::factory()->create();
        $this->actingAs(User::factory()->responsable()->create());

        Livewire::test(ManagePickupRequests::class)
            ->callAction(TestAction::make('refuse')->table($pickup), data: ['admin_note' => 'Gravats de chantier non acceptés'])
            ->assertHasNoFormErrors();

        $pickup->refresh();
        $this->assertSame(PickupStatus::Refusee, $pickup->status);
        $this->assertSame('Gravats de chantier non acceptés', $pickup->admin_note);
    }
}
