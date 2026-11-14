<?php

namespace Database\Factories;

use App\Enums\PointReason;
use App\Models\PointAward;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PointAward>
 */
class PointAwardFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'reason' => PointReason::Nettoyage,
            'points' => PointReason::Nettoyage->points(),
            'awardable_type' => (new Report)->getMorphClass(),
            'awardable_id' => Report::factory(),
        ];
    }
}
