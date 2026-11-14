<?php

namespace Database\Factories;

use App\Models\Truck;
use App\Models\TruckPassage;
use App\Models\Village;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TruckPassage>
 */
class TruckPassageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'truck_id' => Truck::factory(),
            'village_id' => Village::factory(),
            'passed_on' => today(),
        ];
    }
}
