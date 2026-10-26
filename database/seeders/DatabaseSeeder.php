<?php

namespace Database\Seeders;

use App\Models\PackingItem;
use App\Models\Trip;
use App\Models\TripParticipant;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $owner = User::factory()->create([
            'name' => 'Demo Owner',
            'email' => 'demo@example.com',
        ]);

        $trip = Trip::factory()->for($owner, 'creator')->create([
            'destination' => 'Bergen',
            'start_date' => now()->addDays(30)->toDateString(),
            'end_date' => now()->addDays(32)->toDateString(),
            'type' => 'family',
            'transport' => 'train',
            'accommodation' => 'cabin',
            'activities' => ['hiking', 'sightseeing'],
            'description' => 'A family weekend in Bergen.',
        ]);

        $trip->members()->attach($owner, ['role' => 'admin']);

        $child = TripParticipant::factory()->for($trip)->create([
            'user_id' => null,
            'name' => 'Demo Child',
            'age' => 8,
        ]);

        PackingItem::factory()->for($trip)->create([
            'name' => 'Phone charger',
            'quantity' => 2,
            'comment' => null,
        ]);

        PackingItem::factory()->for($trip)->for($child, 'participant')->create([
            'name' => 'Water bottle',
            'quantity' => 1,
            'comment' => null,
        ]);
    }
}
