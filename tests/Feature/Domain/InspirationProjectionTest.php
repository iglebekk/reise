<?php

use App\Actions\RefreshTripInspirationProjection;
use App\Models\PackingItem;
use App\Models\Trip;
use App\Models\TripInspirationProjection;
use App\Models\TripParticipant;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->travelTo(now()->setDate(2026, 10, 25)->startOfDay());
});

it('projects only canonical item names without participant or comment data', function () {
    $trip = Trip::factory()->create([
        'start_date' => '2026-10-01',
        'end_date' => '2026-10-01',
    ]);
    $participant = TripParticipant::factory()->for($trip)->create([
        'name' => 'Private Person',
        'age' => 37,
    ]);
    PackingItem::factory()->for($trip)->create(['name' => ' phone ', 'comment' => 'private@example.com']);
    PackingItem::factory()->for($trip)->create(['name' => 'PHONE', 'comment' => 'Private medication details']);
    PackingItem::factory()->for($trip)->create([
        'name' => 'Private Person passport',
        'comment' => 'private@example.com',
    ]);
    PackingItem::factory()->for($trip)->for($participant, 'participant')->create([
        'name' => 'Passport',
        'comment' => 'Personal note',
    ]);

    $projection = app(RefreshTripInspirationProjection::class)->handle($trip);
    $projectedNames = $projection->items->pluck('canonical_name')->sort()->values()->all();

    expect($projection)->toBeInstanceOf(TripInspirationProjection::class)
        ->and($projectedNames)->toBe(['Passport', 'Phone'])
        ->and($projection->getAttributes())->not->toHaveKey('destination')
        ->and($projection->items->pluck('canonical_name')->implode(' '))->not->toContain('Private Person')
        ->and($projection->items->pluck('canonical_name')->implode(' '))->not->toContain('private@example.com');
});

it('assigns the defined duration group at each boundary', function (int $duration, string $expectedGroup) {
    $startDate = now()->subDays(30)->toDateString();
    $trip = Trip::factory()->create([
        'start_date' => $startDate,
        'end_date' => now()->subDays(31 - $duration)->toDateString(),
    ]);

    $projection = app(RefreshTripInspirationProjection::class)->handle($trip);

    expect($projection?->duration_group)->toBe($expectedGroup);
})->with([
    'one day' => [1, 'day'],
    'two days' => [2, 'weekend'],
    'three days' => [3, 'weekend'],
    'four days' => [4, 'week'],
    'seven days' => [7, 'week'],
    'eight days' => [8, 'extended'],
]);

it('removes projections for trips that have not ended before today', function () {
    $trip = Trip::factory()->create([
        'start_date' => '2026-10-25',
        'end_date' => '2026-10-25',
    ]);
    $existingProjection = TripInspirationProjection::factory()->create([
        'trip_id' => $trip->id,
        'owner_id' => $trip->created_by_user_id,
    ]);

    $projection = app(RefreshTripInspirationProjection::class)->handle($trip);

    expect($projection)->toBeNull()
        ->and($trip->inspirationProjection()->exists())->toBeFalse();
    $this->assertModelMissing($existingProjection);
});

it('removes an existing projection when source categories are not allowlisted', function () {
    $trip = Trip::factory()->create([
        'start_date' => '2026-10-01',
        'end_date' => '2026-10-01',
    ]);
    $existingProjection = TripInspirationProjection::factory()->create([
        'trip_id' => $trip->id,
        'owner_id' => $trip->created_by_user_id,
    ]);
    $trip->forceFill(['type' => 'private'])->save();

    $projection = app(RefreshTripInspirationProjection::class)->handle($trip);

    expect($projection)->toBeNull()
        ->and($trip->inspirationProjection()->exists())->toBeFalse();
    $this->assertModelMissing($existingProjection);
});

it('replaces projected items and metadata on repeated refreshes', function () {
    $trip = Trip::factory()->create([
        'start_date' => '2026-10-01',
        'end_date' => '2026-10-01',
    ]);
    PackingItem::factory()->for($trip)->create(['name' => 'Phone']);
    $action = app(RefreshTripInspirationProjection::class);
    $firstProjection = $action->handle($trip);

    $trip->packingItems()->delete();
    PackingItem::factory()->for($trip)->create(['name' => 'Passport']);
    $trip->forceFill(['transport' => 'plane'])->save();

    $secondProjection = $action->handle($trip->refresh());

    expect($secondProjection->id)->toBe($firstProjection->id)
        ->and($secondProjection->transport)->toBe('plane')
        ->and($secondProjection->items->pluck('canonical_name')->all())->toBe(['Passport']);
});
