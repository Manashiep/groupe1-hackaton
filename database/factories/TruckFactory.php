<?php

namespace Database\Factories;

use App\Models\Truck;
use App\Models\Village;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Truck>
 */
class TruckFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Camion '.fake()->unique()->numberBetween(1, 999),
            'plate' => 'CMR-'.fake()->numberBetween(100, 999),
            'village_id' => Village::factory(),
            'color' => fake()->hexColor(),
        ];
    }
}
