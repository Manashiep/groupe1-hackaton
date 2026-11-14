<?php

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Models\Due;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'due_id' => Due::factory(),
            'user_id' => fn (array $attributes) => Due::find($attributes['due_id'])->user_id,
            'provider' => 'simupay',
            'provider_id' => 'sp_'.fake()->unique()->bothify('????????????????'),
            'amount' => 300,
            'status' => PaymentStatus::Pending,
        ];
    }
}
