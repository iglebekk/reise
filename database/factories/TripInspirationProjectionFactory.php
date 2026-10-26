<?php

namespace Database\Factories;

use App\Models\Trip;
use App\Models\TripInspirationProjection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TripInspirationProjection>
 */
class TripInspirationProjectionFactory extends Factory
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
            'owner_id' => fn (array $attributes): int => Trip::query()
                ->findOrFail($attributes['trip_id'])
                ->created_by_user_id,
            'trip_type' => fake()->randomElement(['family', 'business', 'adventure']),
            'transport' => fake()->randomElement(['car', 'train', 'plane']),
            'accommodation' => fake()->randomElement(['hotel', 'cabin', 'camping']),
            'duration_group' => fake()->randomElement(['day', 'weekend', 'week', 'extended']),
        ];
    }
}
