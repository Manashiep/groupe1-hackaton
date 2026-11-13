<?php

namespace Database\Factories;

use App\Enums\PostType;
use App\Models\Association;
use App\Models\AssociationPost;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssociationPost>
 */
class AssociationPostFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'association_id' => Association::factory()->active(),
            'type' => PostType::Publication,
            'body' => fake()->sentence(),
        ];
    }
}
