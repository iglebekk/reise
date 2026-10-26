<?php

namespace Database\Factories;

use App\Models\Trip;
use App\Models\TripInvitation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<TripInvitation>
 */
class TripInvitationFactory extends Factory
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
            'email' => Str::lower(fake()->safeEmail()),
            'role' => 'member',
            'token_hash' => hash('sha256', Str::random(40)),
            'expires_at' => now()->addDays(7),
            'accepted_at' => null,
        ];
    }
}
