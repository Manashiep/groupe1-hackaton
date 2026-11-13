<?php

namespace Database\Factories;

use App\Enums\Role;
use App\Models\Truck;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => Role::Citoyen,
            'is_active' => true,
        ];
    }

    /**
     * Agent de collecte rattaché à un camion.
     */
    public function agent(?Truck $truck = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'role' => Role::Agent,
            'truck_id' => $truck?->id ?? Truck::factory(),
        ]);
    }

    public function responsable(): static
    {
        return $this->state(fn (array $attributes): array => ['role' => Role::Responsable]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes): array => ['role' => Role::Admin]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
