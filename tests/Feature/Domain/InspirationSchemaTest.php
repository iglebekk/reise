<?php

use App\Models\Trip;
use App\Models\TripInspirationItem;
use App\Models\TripInspirationProjection;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('stores only allowlisted metadata and canonical item names in the projection', function () {
    $projection = TripInspirationProjection::factory()->create([
        'trip_type' => 'family',
        'transport' => 'train',
        'accommodation' => 'hotel',
        'duration_group' => 'weekend',
    ]);
    $item = $projection->items()->create(['canonical_name' => 'Phone charger']);

    expect($projection->owner_id)->toBe($projection->trip->created_by_user_id)
        ->and($projection->toArray())->not->toHaveKey('trip_id')
        ->and($projection->toArray())->not->toHaveKey('owner_id')
        ->and($item->toArray())->not->toHaveKey('projection_id');

    expect(array_diff(Schema::getColumnListing('trip_inspiration_projections'), [
        'id', 'trip_id', 'owner_id', 'trip_type', 'transport', 'accommodation', 'duration_group', 'created_at', 'updated_at',
    ]))->toBe([])
        ->and(array_intersect(Schema::getColumnListing('trip_inspiration_items'), [
            'trip_id', 'owner_id', 'destination', 'description', 'participant_id', 'email', 'comment', 'packing_item_id', 'source_item_id',
        ]))->toBe([]);

    expect(fn () => TripInspirationProjection::factory()->create(['trip_type' => 'private']))
        ->toThrow(QueryException::class);

    expect(fn () => TripInspirationItem::factory()->create(['canonical_name' => 'Private item']))
        ->toThrow(QueryException::class);
});

it('enforces one projection per trip and one canonical item per projection', function () {
    $projection = TripInspirationProjection::factory()->create();

    expect(fn () => TripInspirationProjection::factory()->create([
        'trip_id' => $projection->trip_id,
        'owner_id' => $projection->owner_id,
    ]))->toThrow(QueryException::class);

    $projection->items()->create(['canonical_name' => 'Phone']);

    expect(fn () => $projection->items()->create(['canonical_name' => 'Phone']))
        ->toThrow(QueryException::class);

    expect($projection->items()->count())->toBe(1);
});

it('enforces projection foreign keys and cascades trip deletion to projected items', function () {
    $owner = User::factory()->create();
    $otherOwner = User::factory()->create();
    $trip = Trip::factory()->create(['created_by_user_id' => $owner->getKey()]);
    $projection = TripInspirationProjection::factory()->create([
        'trip_id' => $trip->getKey(),
        'owner_id' => $owner->getKey(),
    ]);
    $item = $projection->items()->create(['canonical_name' => 'Passport']);

    expect(fn () => TripInspirationProjection::factory()->create([
        'trip_id' => PHP_INT_MAX,
        'owner_id' => $owner->getKey(),
    ]))->toThrow(QueryException::class);

    $otherTrip = Trip::factory()->create(['created_by_user_id' => $owner->getKey()]);

    expect(fn () => TripInspirationProjection::factory()->create([
        'trip_id' => $otherTrip->getKey(),
        'owner_id' => $otherOwner->getKey(),
    ]))->toThrow(QueryException::class);

    $trip->delete();

    $this->assertModelMissing($projection);
    $this->assertModelMissing($item);
});
