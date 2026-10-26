<?php

use App\Actions\DeleteTrip;
use App\Actions\RefreshTripInspirationProjection;
use App\Actions\UpdateTrip;
use App\Models\PackingItem;
use App\Models\Trip;
use App\Models\TripInspirationProjection;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->travelTo(now()->setDate(2026, 10, 26)->startOfDay());
});

it('lets a member partially update metadata and refreshes duration and projection', function () {
    $admin = User::factory()->create();
    $member = User::factory()->create();
    $trip = Trip::factory()->create([
        'start_date' => '2026-10-01',
        'end_date' => '2026-10-03',
        'destination' => 'Old destination',
        'description' => 'Keep this description',
        'activities' => [],
    ]);
    $trip->members()->attach($admin, ['role' => 'admin']);
    $trip->members()->attach($member, ['role' => 'member']);
    $member->setCurrentTenant($trip);
    PackingItem::factory()->for($trip)->create(['name' => 'Phone']);

    app(RefreshTripInspirationProjection::class)->handle($trip);

    $updatedTrip = (new UpdateTrip)($member, $trip, [
        'start_date' => '2026-10-05',
        'end_date' => '2026-10-05',
        'type' => 'business',
    ]);
    $projection = $updatedTrip->inspirationProjection()->firstOrFail();

    expect($updatedTrip->destination)->toBe('Old destination')
        ->and($updatedTrip->description)->toBe('Keep this description')
        ->and($updatedTrip->durationInDays())->toBe(1)
        ->and($projection->trip_type)->toBe('business')
        ->and($projection->duration_group)->toBe('day')
        ->and($projection->items->pluck('canonical_name')->all())->toBe(['Phone']);
});

it('rejects invalid date ordering and unapproved catalog values', function (array $attributes) {
    $member = User::factory()->create();
    $trip = Trip::factory()->create([
        'start_date' => '2026-10-01',
        'end_date' => '2026-10-03',
    ]);
    $trip->members()->attach($member, ['role' => 'member']);
    $member->setCurrentTenant($trip);

    expect(fn () => (new UpdateTrip)($member, $trip, $attributes))
        ->toThrow(ValidationException::class);
})->with([
    'end before start' => [['start_date' => '2026-10-05', 'end_date' => '2026-10-04']],
    'unknown type' => [['type' => 'private']],
    'unknown activity' => [['activities' => ['private activity']]],
]);

it('denies trip updates when the trip is not the actors current tenant', function () {
    $member = User::factory()->create();
    $trip = Trip::factory()->create();
    $otherTrip = Trip::factory()->create();
    $trip->members()->attach($member, ['role' => 'member']);
    $otherTrip->members()->attach($member, ['role' => 'member']);
    $member->setCurrentTenant($otherTrip);

    expect(fn () => (new UpdateTrip)($member, $trip, ['destination' => 'Changed']))
        ->toThrow(AuthorizationException::class);
});

it('lets an admin delete the trip and cascades the projection and projected items', function () {
    $admin = User::factory()->create();
    $trip = Trip::factory()->create();
    $trip->members()->attach($admin, ['role' => 'admin']);
    $admin->setCurrentTenant($trip);
    $projection = TripInspirationProjection::factory()->create([
        'trip_id' => $trip->getKey(),
        'owner_id' => $trip->created_by_user_id,
    ]);
    $projectionItem = $projection->items()->create(['canonical_name' => 'Passport']);

    (new DeleteTrip)($admin, $trip);

    $this->assertModelMissing($trip);
    $this->assertModelMissing($projection);
    $this->assertModelMissing($projectionItem);
});

it('denies trip deletion by a member', function () {
    $member = User::factory()->create();
    $trip = Trip::factory()->create();
    $trip->members()->attach($member, ['role' => 'member']);
    $member->setCurrentTenant($trip);

    expect(fn () => (new DeleteTrip)($member, $trip))
        ->toThrow(AuthorizationException::class);

    $this->assertModelExists($trip);
});
