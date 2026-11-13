<?php

namespace Tests\Feature\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LoginControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string, string}>
     */
    public static function homes(): array
    {
        return [
            'citizen goes to the map' => ['citoyen', '/carte'],
            'agent goes to the route' => ['agent', '/agent/tournee'],
            'responsable goes to the admin panel' => ['responsable', '/admin'],
        ];
    }

    #[DataProvider('homes')]
    public function test_login_redirects_each_role_to_its_home(string $state, string $home): void
    {
        $factory = User::factory();
        $user = ($state === 'citoyen' ? $factory : $factory->{$state}())->create();

        $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(url($home));
    }

    public function test_deactivated_account_cannot_log_in(): void
    {
        $user = User::factory()->create(['is_active' => false]);

        $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors(['email' => 'E-mail ou mot de passe incorrect.']);

        $this->assertGuest();
    }
}
