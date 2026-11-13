<?php

namespace Tests\Feature\Http\Controllers\Agent;

use App\Enums\NoticeKind;
use App\Models\Truck;
use App\Models\TruckNotice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TruckNoticeControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_agent_warns_residents_that_the_round_is_not_finished(): void
    {
        $truck = Truck::factory()->create();

        $this->actingAs(User::factory()->agent($truck)->create())
            ->post(route('agent.notices.store'), ['kind' => 'non_terminee', 'message' => 'On repasse demain à 7h'])
            ->assertSessionHas('success');

        $notice = TruckNotice::sole();
        $this->assertTrue($notice->truck->is($truck));
        $this->assertSame(NoticeKind::NonTerminee, $notice->kind);
    }

    public function test_agent_without_truck_cannot_send_alerts(): void
    {
        $agent = User::factory()->agent()->create(['truck_id' => null]);

        $this->actingAs($agent)
            ->post(route('agent.notices.store'), ['kind' => 'retard'])
            ->assertForbidden();
    }
}
