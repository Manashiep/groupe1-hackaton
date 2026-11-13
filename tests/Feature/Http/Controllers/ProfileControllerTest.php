<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Association;
use App\Models\Report;
use App\Models\User;
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
}
