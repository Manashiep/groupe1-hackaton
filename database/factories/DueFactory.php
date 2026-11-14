<?php

namespace Database\Factories;

use App\Enums\DueStatus;
use App\Models\Due;
use App\Models\TruckPassage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Due>
 */
class DueFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'truck_passage_id' => TruckPassage::factory(),
            'amount' => 300,
            'status' => DueStatus::APayer,
        ];
    }
}
