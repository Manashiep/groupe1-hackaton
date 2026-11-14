<?php

namespace Tests\Feature\Http\Middleware;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnsureUserIsActiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_deactivated_user_is_logged_out_on_next_page(): void
    {
        $citizen = User::factory()->create();
        $this->actingAs($citizen)->get(route('map'))->assertOk();

        $citizen->update(['is_active' => false]);

        $this->get(route('map'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}
