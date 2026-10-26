<?php

use App\Actions\CreatePackingItem;
use App\Actions\DeletePackingItem;
use App\Actions\UpdatePackingItem;
use App\Models\PackingItem;
use App\Models\Trip;
use App\Models\TripParticipant;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

it('lets a trip member create shared and participant packing items', function () {
    $member = User::factory()->create();
    $trip = Trip::factory()->create();
    $trip->members()->attach($member, ['role' => 'member']);
    $member->setCurrentTenant($trip);
    $participant = TripParticipant::factory()->for($trip)->create();

    $sharedItem = (new CreatePackingItem)($member, $trip, ['name' => 'First-aid kit']);
    $participantItem = (new CreatePackingItem)($member, $trip, [
        'name' => 'Water bottle',
        'quantity' => 2,
        'participant_id' => $participant->getKey(),
    ]);

    expect($sharedItem->participant_id)->toBeNull()
        ->and($sharedItem->quantity)->toBe(1)
        ->and($participantItem->participant_id)->toBe($participant->getKey())
        ->and($participantItem->quantity)->toBe(2);
});

it('preserves omitted fields during a partial update', function () {
    $member = User::factory()->create();
    $trip = Trip::factory()->create();
    $trip->members()->attach($member, ['role' => 'member']);
    $member->setCurrentTenant($trip);
    $participant = TripParticipant::factory()->for($trip)->create();
    $item = PackingItem::factory()->for($trip)->create([
        'participant_id' => $participant->getKey(),
        'name' => 'Old name',
        'quantity' => 3,
        'comment' => 'Keep this note',
    ]);

    $updatedItem = (new UpdatePackingItem)($member, $trip, $item->getKey(), ['name' => 'New name']);

    expect($updatedItem->name)->toBe('New name')
        ->and($updatedItem->quantity)->toBe(3)
        ->and($updatedItem->comment)->toBe('Keep this note')
        ->and($updatedItem->participant_id)->toBe($participant->getKey());
});

it('rejects missing names and non-positive quantities', function () {
    $member = User::factory()->create();
    $trip = Trip::factory()->create();
    $trip->members()->attach($member, ['role' => 'member']);
    $member->setCurrentTenant($trip);
    $item = PackingItem::factory()->for($trip)->create(['quantity' => 2]);

    expect(fn () => (new CreatePackingItem)($member, $trip, ['name' => 'Kit', 'quantity' => 0]))
        ->toThrow(ValidationException::class);

    expect(fn () => (new CreatePackingItem)($member, $trip, ['quantity' => 1]))
        ->toThrow(ValidationException::class);

    expect(fn () => (new UpdatePackingItem)($member, $trip, $item->getKey(), ['quantity' => -2]))
        ->toThrow(ValidationException::class);

    expect($trip->packingItems()->count())->toBe(1)
        ->and($item->fresh()->quantity)->toBe(2);
});

it('hides a participant from another trip during item creation', function () {
    $member = User::factory()->create();
    $trip = Trip::factory()->create();
    $otherTrip = Trip::factory()->create();
    $trip->members()->attach($member, ['role' => 'member']);
    $member->setCurrentTenant($trip);
    $otherParticipant = TripParticipant::factory()->for($otherTrip)->create();

    expect(fn () => (new CreatePackingItem)($member, $trip, [
        'name' => 'Private item',
        'participant_id' => $otherParticipant->getKey(),
    ]))->toThrow(ModelNotFoundException::class);

    expect($trip->packingItems()->count())->toBe(0);
});

it('hides an item from another trip during update and delete', function () {
    $member = User::factory()->create();
    $trip = Trip::factory()->create();
    $otherTrip = Trip::factory()->create();
    $trip->members()->attach($member, ['role' => 'member']);
    $member->setCurrentTenant($trip);
    $otherItem = PackingItem::factory()->for($otherTrip)->create();

    expect(fn () => (new UpdatePackingItem)($member, $trip, $otherItem->getKey(), ['name' => 'Changed']))
        ->toThrow(ModelNotFoundException::class);

    expect(fn () => (new DeletePackingItem)($member, $trip, $otherItem->getKey()))
        ->toThrow(ModelNotFoundException::class);

    $this->assertModelExists($otherItem);
});

it('denies a user without trip membership from changing packing items', function () {
    $user = User::factory()->create();
    $trip = Trip::factory()->create();

    expect(fn () => (new CreatePackingItem)($user, $trip, ['name' => 'Unauthorized item']))
        ->toThrow(AuthorizationException::class);

    expect($trip->packingItems()->count())->toBe(0);
});

it('deletes a packing item scoped to the current trip', function () {
    $member = User::factory()->create();
    $trip = Trip::factory()->create();
    $trip->members()->attach($member, ['role' => 'member']);
    $member->setCurrentTenant($trip);
    $item = PackingItem::factory()->for($trip)->create();

    (new DeletePackingItem)($member, $trip, $item->getKey());

    $this->assertModelMissing($item);
});
