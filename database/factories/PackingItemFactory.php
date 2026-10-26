<?php

namespace Database\Factories;

use App\Models\PackingItem;
use App\Models\Trip;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PackingItem>
 */
class PackingItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'trip_id' => Trip::factory(),
            'participant_id' => null,
            'name' => fake()->word(),
            'quantity' => 1,
            'comment' => fake()->optional()->sentence(),
        ];
    }
}
