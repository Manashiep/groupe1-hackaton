<?php

namespace Database\Factories;

use App\Enums\ComplaintReason;
use App\Models\Truck;
use App\Models\TruckComplaint;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TruckComplaint>
 */
class TruckComplaintFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'truck_id' => Truck::factory(),
            'reason' => ComplaintReason::Retard,
            'message' => fake()->sentence(),
        ];
    }
}
