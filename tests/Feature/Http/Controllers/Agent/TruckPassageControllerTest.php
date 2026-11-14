<?php

namespace Tests\Feature\Http\Controllers\Agent;

use App\Enums\DueStatus;
use App\Models\Due;
use App\Models\Truck;
use App\Models\User;
use App\Models\Village;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TruckPassageControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{Truck, Village, User}
     */
    private function agentServing(): array
    {
        $truck = Truck::factory()->create();
        $village = Village::factory()->create();
        $truck->villages()->attach($village);

        return [$truck, $village, User::factory()->agent($truck)->create()];
    }

    public function test_confirming_a_passage_bills_only_active_residents_of_that_village(): void
    {
        config(['cleancomores.due_amount' => 300]);
        [, $village, $agent] = $this->agentServing();
        $resident = User::factory()->create(['home_village_id' => $village->id]);
        User::factory()->create(['home_village_id' => Village::factory()->create()->id]);
        User::factory()->create(['home_village_id' => $village->id, 'is_active' => false]);

        $this->actingAs($agent)
            ->post(route('agent.passages.store', $village))
            ->assertSessionHas('success');

        $due = Due::sole();
        $this->assertTrue($due->user->is($resident));
        $this->assertSame(300, $due->amount);
        $this->assertSame(DueStatus::APayer, $due->status);
        $this->assertTrue($due->passage->passed_on->isToday());
    }

    public function test_confirming_twice_the_same_day_does_not_bill_twice(): void
    {
        [, $village, $agent] = $this->agentServing();
        User::factory()->create(['home_village_id' => $village->id]);

        $this->actingAs($agent)->post(route('agent.passages.store', $village));
        $this->actingAs($agent)->post(route('agent.passages.store', $village));

        $this->assertDatabaseCount('truck_passages', 1);
        $this->assertDatabaseCount('dues', 1);
    }

    public function test_agent_cannot_confirm_a_village_his_truck_does_not_serve(): void
    {
        [, , $agent] = $this->agentServing();
        $otherVillage = Village::factory()->create();

        $this->actingAs($agent)
            ->post(route('agent.passages.store', $otherVillage))
            ->assertForbidden();

        $this->assertDatabaseCount('truck_passages', 0);
    }

    public function test_citizens_cannot_confirm_a_passage(): void
    {
        $village = Village::factory()->create();

        $this->actingAs(User::factory()->create())
            ->post(route('agent.passages.store', $village))
            ->assertForbidden();
    }
}
