<?php

namespace Database\Factories;

use App\Models\Trip;
use App\Models\User;
use App\Services\InspirationCatalog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Trip>
 */
class TripFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'created_by_user_id' => User::factory(),
            'destination' => fake()->city(),
            'start_date' => now()->addDays(30)->toDateString(),
            'end_date' => now()->addDays(32)->toDateString(),
            'type' => fake()->randomElement(['family', 'business', 'adventure']),
            'transport' => fake()->randomElement(['car', 'train', 'plane']),
            'accommodation' => fake()->randomElement(['hotel', 'cabin', 'camping']),
            'activities' => fake()->randomElements(InspirationCatalog::activityOptions(), 3),
            'description' => fake()->optional()->sentence(),
        ];
    }
}
