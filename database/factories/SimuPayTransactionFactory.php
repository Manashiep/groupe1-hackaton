<?php

namespace Database\Factories;

use App\Models\SimuPayTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SimuPayTransaction>
 */
class SimuPayTransactionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'amount' => 300,
            'currency' => 'KMF',
            'merchant_reference' => 'due_'.fake()->unique()->numberBetween(1, 99999),
            'description' => 'Redevance de collecte',
            'callback_url' => 'http://127.0.0.1:8000/paiements/webhook',
            'return_url' => 'http://127.0.0.1:8000/moi',
            'status' => SimuPayTransaction::STATUS_PENDING,
        ];
    }
}
