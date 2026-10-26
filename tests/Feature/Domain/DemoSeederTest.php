<?php

use App\Actions\CreateTripWithAdmin;
use App\Models\PackingItem;
use App\Models\Trip;
use App\Models\TripParticipant;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('seeds a verified owner with an admin trip, child participant, and factory-backed packing items', function () {
    $this->artisan('db:seed', [
        '--class' => DatabaseSeeder::class,
        '--no-interaction' => true,
    ])->assertExitCode(0);

    $owner = User::query()->where('email', 'demo@example.com')->firstOrFail();
    $trip = Trip::query()->where('created_by_user_id', $owner->getKey())->firstOrFail();
    $child = TripParticipant::query()->where('trip_id', $trip->getKey())->firstOrFail();
    $packingItems = PackingItem::query()->where('trip_id', $trip->getKey())->get();

    expect($owner->hasVerifiedEmail())->toBeTrue()
        ->and($trip->members()->whereKey($owner->getKey())->first()?->pivot?->role)->toBe('admin')
        ->and($trip->members()->wherePivot('role', 'admin')->count())->toBe(1)
        ->and($child->user_id)->toBeNull()
        ->and($child->age)->toBe(8)
        ->and($packingItems->pluck('name')->sort()->values()->all())->toBe(['Phone charger', 'Water bottle'])
        ->and($packingItems->whereNotNull('participant_id')->first()?->participant_id)->toBe($child->getKey())
        ->and($trip->activities)->toBe(['hiking', 'sightseeing']);
});

it('allows a user-created trip to start with an empty packing list', function () {
    $creator = User::factory()->create();

    $trip = (new CreateTripWithAdmin)($creator, [
        'destination' => 'Tromsø',
        'start_date' => now()->addDays(10)->toDateString(),
        'end_date' => now()->addDays(10)->toDateString(),
        'type' => 'adventure',
        'transport' => 'plane',
        'accommodation' => 'hotel',
        'activities' => [],
    ]);

    expect($trip->members()->whereKey($creator->getKey())->first()?->pivot?->role)->toBe('admin')
        ->and($trip->packingItems()->count())->toBe(0);
});
