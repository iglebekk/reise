<?php

use App\Models\PackingItem;
use App\Models\Trip;
use App\Models\TripInspirationProjection;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->travelTo(now()->setDate(2026, 10, 25)->startOfDay());
});

it('projects a trip when it passes into history and leaves future trips unprojected', function () {
    $newlyEndedTrip = Trip::factory()->create([
        'start_date' => '2026-10-26',
        'end_date' => '2026-10-26',
    ]);
    PackingItem::factory()->for($newlyEndedTrip)->create(['name' => 'Phone']);
    $futureTrip = Trip::factory()->create([
        'start_date' => '2026-10-28',
        'end_date' => '2026-10-29',
    ]);

    expect($newlyEndedTrip->inspirationProjection()->exists())->toBeFalse()
        ->and($futureTrip->inspirationProjection()->exists())->toBeFalse();

    $this->travelTo(now()->addDays(2));

    $this->artisan('inspiration:projections-refresh')->assertExitCode(0);

    $projection = $newlyEndedTrip->inspirationProjection()->firstOrFail();

    expect($projection->items->pluck('canonical_name')->all())->toBe(['Phone'])
        ->and($futureTrip->inspirationProjection()->exists())->toBeFalse()
        ->and(TripInspirationProjection::query()->count())->toBe(1);
});

it('refreshes projections whose metadata no longer matches the trip', function () {
    $trip = Trip::factory()->create([
        'start_date' => '2026-10-01',
        'end_date' => '2026-10-01',
        'type' => 'family',
    ]);
    $projection = TripInspirationProjection::factory()->create([
        'trip_id' => $trip->getKey(),
        'owner_id' => $trip->created_by_user_id,
        'trip_type' => 'family',
    ]);
    $trip->forceFill(['type' => 'business'])->save();

    $this->artisan('inspiration:projections-refresh')->assertExitCode(0);

    expect($projection->fresh()->trip_type)->toBe('business')
        ->and(TripInspirationProjection::query()->count())->toBe(1);
});
