<?php

namespace Database\Factories;

use App\Models\Trip;
use App\Models\TripParticipant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TripParticipant>
 */
class TripParticipantFactory extends Factory
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
            'user_id' => null,
            'name' => fake()->name(),
            'age' => fake()->numberBetween(0, 17),
        ];
    }
}
