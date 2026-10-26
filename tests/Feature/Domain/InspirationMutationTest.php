<?php

use App\Actions\CreatePackingItem;
use App\Actions\CreateTripWithAdmin;
use App\Actions\DeletePackingItem;
use App\Actions\UpdatePackingItem;
use App\Models\PackingItem;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->travelTo(now()->setDate(2026, 10, 25)->startOfDay());
});

it('projects newly created historical trips and excludes future trips', function () {
    $creator = User::factory()->create();
    $createTrip = new CreateTripWithAdmin;

    $pastTrip = $createTrip($creator, [
        'destination' => 'Bergen',
        'start_date' => '2026-10-01',
        'end_date' => '2026-10-01',
        'type' => 'family',
        'transport' => 'train',
        'accommodation' => 'hotel',
        'activities' => [],
    ]);
    $futureTrip = $createTrip($creator, [
        'destination' => 'Oslo',
        'start_date' => '2026-11-01',
        'end_date' => '2026-11-02',
        'type' => 'business',
        'transport' => 'plane',
        'accommodation' => 'hotel',
        'activities' => [],
    ]);

    expect($pastTrip->inspirationProjection()->exists())->toBeTrue()
        ->and($futureTrip->inspirationProjection()->exists())->toBeFalse();
});

it('refreshes canonical items after add update and delete without losing untouched items', function () {
    $member = User::factory()->create();
    $trip = Trip::factory()->create([
        'start_date' => '2026-10-01',
        'end_date' => '2026-10-01',
    ]);
    $trip->members()->attach($member, ['role' => 'member']);
    $member->setCurrentTenant($trip);

    $phone = (new CreatePackingItem)($member, $trip, ['name' => 'Phone']);
    $passport = (new CreatePackingItem)($member, $trip, ['name' => 'Passport']);

    expect($trip->inspirationProjection->items->pluck('canonical_name')->sort()->values()->all())
        ->toBe(['Passport', 'Phone']);

    (new UpdatePackingItem)($member, $trip, $phone->getKey(), [
        'name' => 'Power bank',
        'comment' => 'Anders private medication note',
    ]);

    expect($trip->inspirationProjection()->first()->items->pluck('canonical_name')->sort()->values()->all())
        ->toBe(['Passport', 'Power bank']);

    (new DeletePackingItem)($member, $trip, $passport->getKey());

    expect($trip->inspirationProjection()->first()->items->pluck('canonical_name')->all())
        ->toBe(['Power bank'])
        ->and($trip->inspirationProjection()->first()->items->pluck('canonical_name')->implode(' '))
        ->not->toContain('Anders');

    $this->assertModelExists($phone);
    $this->assertModelMissing($passport);
});

it('never projects a private looking packing item name', function () {
    $member = User::factory()->create();
    $trip = Trip::factory()->create([
        'start_date' => '2026-10-01',
        'end_date' => '2026-10-01',
    ]);
    $trip->members()->attach($member, ['role' => 'member']);
    $member->setCurrentTenant($trip);

    (new CreatePackingItem)($member, $trip, ['name' => 'Anders private medication']);

    expect($trip->inspirationProjection()->first()->items)->toHaveCount(0)
        ->and(PackingItem::query()->where('trip_id', $trip->getKey())->value('name'))
        ->toBe('Anders private medication');
});
