<?php

namespace Database\Factories;

use App\Models\Village;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Village>
 */
class VillageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->city(),
            'ile' => 'Ngazidja',
            'latitude' => fake()->randomFloat(5, -11.76, -11.65),
            'longitude' => fake()->randomFloat(5, 43.23, 43.29),
        ];
    }
}
