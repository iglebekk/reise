<?php

use App\Models\PackingItem;
use App\Models\Trip;
use App\Models\TripParticipant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lets a trip member create, update, and delete a shared packing item', function () {
    $user = User::factory()->create();
    $trip = Trip::factory()->create();
    $trip->members()->attach($user, ['role' => 'member']);
    $createResponse = $this->actingAs($user)->post(route('trips.packing-items.store', $trip), [
        'name' => 'Travel adapter',
        'quantity' => 1,
        'participant_id' => null,
    ]);
    $createResponse->assertRedirect(route('trips.show', $trip));
    $this->assertDatabaseHas('packing_items', [
        'trip_id' => $trip->getKey(),
        'name' => 'Travel adapter',
        'participant_id' => null,
    ]);

    $item = PackingItem::factory()->for($trip)->create([
        'name' => 'Water bottle',
        'quantity' => 1,
        'comment' => 'Keep it empty at security.',
    ]);
    $untouchedItem = PackingItem::factory()->for($trip)->create([
        'name' => 'Rain jacket',
        'quantity' => 2,
        'comment' => 'Leave packed.',
    ]);

    $this->actingAs($user)
        ->get(route('trips.show', $trip))
        ->assertOk()
        ->assertSee('Shared items')
        ->assertSee('Water bottle')
        ->assertSee('Rain jacket');

    $this->patch(route('trips.packing-items.update', [$trip, $item]), [
        'name' => 'Reusable bottle',
        'quantity' => 2,
        'comment' => 'Fill it before leaving.',
        'participant_id' => null,
    ])->assertRedirect(route('trips.show', $trip));

    $this->assertDatabaseHas('packing_items', [
        'id' => $item->getKey(),
        'name' => 'Reusable bottle',
        'quantity' => 2,
        'comment' => 'Fill it before leaving.',
        'participant_id' => null,
    ]);
    $this->assertDatabaseHas('packing_items', [
        'id' => $untouchedItem->getKey(),
        'name' => 'Rain jacket',
        'quantity' => 2,
        'comment' => 'Leave packed.',
    ]);

    $this->delete(route('trips.packing-items.destroy', [$trip, $item]))
        ->assertRedirect(route('trips.show', $trip));

    $this->assertDatabaseMissing('packing_items', ['id' => $item->getKey()]);
});

it('keeps shared and participant items in their respective lists', function () {
    $user = User::factory()->create();
    $trip = Trip::factory()->create();
    $trip->members()->attach($user, ['role' => 'admin']);
    $participant = TripParticipant::factory()->for($trip)->create(['name' => 'Riley']);
    $sharedItem = PackingItem::factory()->for($trip)->create(['name' => 'Shared towel']);
    $personalItem = PackingItem::factory()->for($trip)->create([
        'participant_id' => $participant->getKey(),
        'name' => 'Riley’s boots',
    ]);
    $this->actingAs($user)->post(route('trips.packing-items.store', $trip), [
        'name' => 'Riley’s book',
        'participant_id' => $participant->getKey(),
    ])->assertRedirect(route('trips.show', $trip));

    $response = $this->actingAs($user)->get(route('trips.show', $trip));

    $response->assertOk()
        ->assertSee('Shared towel')
        ->assertSee('Riley’s boots')
        ->assertSee('Riley’s book')
        ->assertSee('Riley’s items');
    expect($sharedItem->participant_id)->toBeNull()
        ->and($personalItem->participant_id)->toBe($participant->getKey());
});

it('validates positive quantities and participant membership', function () {
    $user = User::factory()->create();
    $trip = Trip::factory()->create();
    $otherTrip = Trip::factory()->create();
    $trip->members()->attach($user, ['role' => 'admin']);
    $participant = TripParticipant::factory()->for($otherTrip)->create();

    $this->actingAs($user)
        ->postJson(route('trips.packing-items.store', $trip), [
            'name' => 'Tent',
            'quantity' => 0,
            'participant_id' => $participant->getKey(),
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['quantity', 'participant_id']);

    $this->assertDatabaseCount('packing_items', 0);
});

it('returns not found when a packing item belongs to another trip', function () {
    $user = User::factory()->create();
    $trip = Trip::factory()->create();
    $otherTrip = Trip::factory()->create();
    $trip->members()->attach($user, ['role' => 'member']);
    $item = PackingItem::factory()->for($otherTrip)->create();

    $this->actingAs($user)
        ->delete(route('trips.packing-items.destroy', [$trip, $item]))
        ->assertNotFound();
});

it('requires authentication and verified email for packing item routes', function () {
    $trip = Trip::factory()->create();
    $this->post(route('trips.packing-items.store', $trip), ['name' => 'Tent'])
        ->assertRedirect(route('login'));

    $unverifiedUser = User::factory()->unverified()->create();
    $trip->members()->attach($unverifiedUser, ['role' => 'admin']);

    $this->actingAs($unverifiedUser)
        ->post(route('trips.packing-items.store', $trip), ['name' => 'Tent'])
        ->assertRedirect(route('verification.notice'));
});
