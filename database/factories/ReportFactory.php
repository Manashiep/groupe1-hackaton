<?php

namespace Database\Factories;

use App\Enums\ReportStatus;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Report>
 */
class ReportFactory extends Factory
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
            'status' => ReportStatus::Nouveau,
        ];
    }

    /**
     * Pris en charge par un citoyen, nettoyage prévu demain matin.
     */
    public function takenBy(?User $taker = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ReportStatus::PrisEnCharge,
            'taken_by_id' => $taker?->id ?? User::factory(),
            'cleanup_at' => today()->addDay()->setTime(8, 0),
        ]);
    }
}
