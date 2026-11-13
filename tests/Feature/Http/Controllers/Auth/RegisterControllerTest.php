<?php

namespace Tests\Feature\Http\Controllers\Auth;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_always_creates_a_citizen_and_opens_the_map(): void
    {
        $this->post(route('register'), [
            'name' => 'Nouvel Habitant',
            'email' => 'nouveau@exemple.km',
            'password' => 'motdepasse1',
            'password_confirmation' => 'motdepasse1',
            'role' => 'admin',
        ])->assertRedirect(route('map'));

        $this->assertSame(Role::Citoyen, User::sole()->role);
        $this->assertAuthenticated();
    }
}
