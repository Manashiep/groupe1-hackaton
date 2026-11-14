<?php

namespace Tests\Feature\Filament\Resources\Trucks\Pages;

use App\Filament\Resources\Trucks\Pages\EditTruck;
use App\Models\Truck;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EditTruckTest extends TestCase
{
    use RefreshDatabase;

    public function test_truck_color_must_be_a_hex_code(): void
    {
        $truck = Truck::factory()->create(['color' => '#1d6b47']);
        $this->actingAs(User::factory()->responsable()->create());

        Livewire::test(EditTruck::class, ['record' => $truck->getKey()])
            ->fillForm(['color' => 'red"><img src=x onerror=alert(1)>'])
            ->call('save')
            ->assertHasFormErrors(['color' => 'regex']);

        $this->assertSame('#1d6b47', $truck->fresh()->color);
    }
}
