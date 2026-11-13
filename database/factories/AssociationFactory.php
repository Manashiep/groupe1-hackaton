<?php

namespace Database\Factories;

use App\Enums\AssociationStatus;
use App\Models\Association;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Association>
 */
class AssociationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Les nettoyeurs de '.fake()->unique()->city(),
            'description' => fake()->paragraph(),
            'leader_id' => User::factory(),
            'status' => AssociationStatus::EnAttente,
            'motivation' => fake()->sentence(),
            'color' => '#1d6b47',
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes): array => ['status' => AssociationStatus::Active]);
    }
}
