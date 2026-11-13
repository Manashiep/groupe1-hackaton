<?php

namespace Database\Factories;

use App\Models\RouteStop;
use App\Models\Truck;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RouteStop>
 */
class RouteStopFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'truck_id' => Truck::factory(),
            'position' => 1,
            'label' => fake()->streetName(),
            'latitude' => fake()->randomFloat(5, -11.76, -11.65),
            'longitude' => fake()->randomFloat(5, 43.23, 43.29),
        ];
    }
}
