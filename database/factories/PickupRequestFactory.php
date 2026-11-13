<?php

namespace Database\Factories;

use App\Enums\PickupStatus;
use App\Models\PickupRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PickupRequest>
 */
class PickupRequestFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'description' => fake()->sentence(),
            'latitude' => fake()->randomFloat(5, -11.76, -11.65),
            'longitude' => fake()->randomFloat(5, 43.23, 43.29),
            'preferred_date' => today()->addDays(2),
            'status' => PickupStatus::EnAttente,
        ];
    }
}
