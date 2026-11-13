<?php

namespace Database\Factories;

use App\Enums\NoticeKind;
use App\Models\Truck;
use App\Models\TruckNotice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TruckNotice>
 */
class TruckNoticeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'truck_id' => Truck::factory(),
            'kind' => NoticeKind::NonTerminee,
            'message' => fake()->sentence(),
        ];
    }
}
