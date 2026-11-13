<?php

namespace Tests\Feature\Filament\Resources\Trucks\Pages;

use App\Filament\Resources\Trucks\Pages\EditTruckRoute;
use App\Models\RouteStop;
use App\Models\Truck;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EditTruckRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_clicking_the_map_appends_a_stop_at_the_end_of_the_route(): void
    {
        $truck = Truck::factory()->create();
        RouteStop::factory()->for($truck)->create(['position' => 1]);
        $this->actingAs(User::factory()->responsable()->create());

        Livewire::test(EditTruckRoute::class, ['record' => $truck->getKey()])
            ->call('addStop', -11.7040, 43.2530)
            ->assertDispatched('route-updated');

        $stop = RouteStop::latest('id')->first();
        $this->assertSame(2, $stop->position);
        $this->assertEqualsWithDelta(-11.7040, $stop->latitude, 0.00001);
    }

    public function test_responsable_moves_reorders_and_deletes_stops(): void
    {
        $truck = Truck::factory()->create();
        $first = RouteStop::factory()->for($truck)->create(['position' => 1]);
        $second = RouteStop::factory()->for($truck)->create(['position' => 2]);
        $third = RouteStop::factory()->for($truck)->create(['position' => 3]);
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(EditTruckRoute::class, ['record' => $truck->getKey()])
            ->call('moveStop', $first->id, -11.7, 43.26)
            ->call('moveStopUp', $third->id)
            ->call('deleteStop', $first->id);

        $this->assertModelMissing($first);
        $this->assertSame(
            [$third->id, $second->id],
            $truck->stops()->pluck('id')->all(),
        );
        $this->assertSame([1, 2], $truck->stops()->pluck('position')->all());
    }

    public function test_stops_of_another_truck_cannot_be_edited(): void
    {
        $truck = Truck::factory()->create();
        $foreignStop = RouteStop::factory()->create(['latitude' => -11.7]);
        $this->actingAs(User::factory()->responsable()->create());

        Livewire::test(EditTruckRoute::class, ['record' => $truck->getKey()])
            ->call('moveStop', $foreignStop->id, -11.6, 43.2)
            ->assertNotFound();

        $this->assertEqualsWithDelta(-11.7, $foreignStop->fresh()->latitude, 0.00001);
    }
}
