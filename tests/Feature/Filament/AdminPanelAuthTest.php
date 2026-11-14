<?php

namespace Tests\Feature\Filament;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPanelAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_sent_to_the_app_login_page(): void
    {
        $this->get('/admin')->assertRedirect(route('login'));
    }

    public function test_mairie_logout_lands_on_the_app_login_page(): void
    {
        $this->actingAs(User::factory()->responsable()->create());

        $this->post(route('filament.admin.auth.logout'))->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_citizen_logging_in_after_trying_the_admin_space_lands_in_the_app(): void
    {
        $citizen = User::factory()->create(['password' => 'password']);
        $this->get('/admin');

        $this->post(route('login'), ['email' => $citizen->email, 'password' => 'password'])
            ->assertRedirect(route('map'));
    }
}
