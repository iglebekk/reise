<?php

namespace Database\Factories;

use App\Models\TripInspirationItem;
use App\Models\TripInspirationProjection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TripInspirationItem>
 */
class TripInspirationItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'projection_id' => TripInspirationProjection::factory(),
            'canonical_name' => fake()->randomElement([
                'Passport',
                'Phone',
                'Phone charger',
                'Power bank',
                'Toothbrush',
                'Toothpaste',
                'Sunscreen',
                'Water bottle',
                'Rain jacket',
                'Walking shoes',
                'Towel',
                'Hat',
            ]),
        ];
    }
}
