<?php

namespace Database\Factories;

use App\Models\Truck;
use App\Models\TruckSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TruckSchedule>
 */
class TruckScheduleFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'truck_id' => Truck::factory(),
            'day_of_week' => fake()->numberBetween(1, 6),
            'start_time' => '07:00',
            'end_time' => '09:00',
        ];
    }
}
