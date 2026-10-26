<?php

use App\Models\PackingItem;
use App\Models\Trip;
use App\Models\TripParticipant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;

uses(RefreshDatabase::class);

test('packing item routes require authentication', function (): void {
    $this->getJson('/api/v1/trips/1/packing-items')->assertUnauthorized();
});

test('a trip member can list create show update and delete packing items', function (): void {
    $user = User::factory()->create();
    $trip = Trip::factory()->create();
    $trip->members()->attach($user, ['role' => 'member']);
    $participant = TripParticipant::factory()->create(['trip_id' => $trip->id]);
    Passport::actingAs($user);

    $this->getJson('/api/v1/trips/'.$trip->id.'/packing-items')
        ->assertOk()
        ->assertJsonCount(0, 'data');

    $created = $this->postJson('/api/v1/trips/'.$trip->id.'/packing-items', [
        'name' => 'Water bottle',
        'quantity' => 2,
        'comment' => 'Reusable',
        'participant_id' => $participant->id,
    ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Water bottle')
        ->assertJsonPath('data.quantity', 2)
        ->assertJsonPath('data.participant_id', $participant->id);

    $itemId = $created->json('data.id');
    $this->assertDatabaseHas('packing_items', [
        'id' => $itemId,
        'trip_id' => $trip->id,
        'participant_id' => $participant->id,
    ]);

    $this->getJson('/api/v1/trips/'.$trip->id.'/packing-items/'.$itemId)
        ->assertOk()
        ->assertJsonPath('data.id', $itemId);

    $this->patchJson('/api/v1/trips/'.$trip->id.'/packing-items/'.$itemId, [
        'name' => 'Water flask',
    ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Water flask')
        ->assertJsonPath('data.quantity', 2)
        ->assertJsonPath('data.comment', 'Reusable')
        ->assertJsonPath('data.participant_id', $participant->id);

    $this->deleteJson('/api/v1/trips/'.$trip->id.'/packing-items/'.$itemId)->assertNoContent();
    $this->assertDatabaseMissing('packing_items', ['id' => $itemId]);
});

test('a shared packing item accepts a null participant and a partial update can clear its assignment', function (): void {
    $user = User::factory()->create();
    $trip = Trip::factory()->create();
    $trip->members()->attach($user, ['role' => 'member']);
    $participant = TripParticipant::factory()->create(['trip_id' => $trip->id]);
    Passport::actingAs($user);

    $item = PackingItem::factory()->create([
        'trip_id' => $trip->id,
        'participant_id' => null,
        'quantity' => 3,
        'comment' => 'Shared list',
    ]);

    $this->patchJson('/api/v1/trips/'.$trip->id.'/packing-items/'.$item->id, [
        'participant_id' => $participant->id,
    ])
        ->assertOk()
        ->assertJsonPath('data.participant_id', $participant->id)
        ->assertJsonPath('data.quantity', 3)
        ->assertJsonPath('data.comment', 'Shared list');

    $this->patchJson('/api/v1/trips/'.$trip->id.'/packing-items/'.$item->id, [
        'participant_id' => null,
    ])
        ->assertOk()
        ->assertJsonPath('data.participant_id', null);
});

test('packing item writes validate names and quantities', function (): void {
    $user = User::factory()->create();
    $trip = Trip::factory()->create();
    $trip->members()->attach($user, ['role' => 'member']);
    Passport::actingAs($user);

    $this->postJson('/api/v1/trips/'.$trip->id.'/packing-items', [
        'name' => '',
        'quantity' => 0,
    ])->assertUnprocessable()->assertJsonValidationErrors(['name', 'quantity']);

    $item = PackingItem::factory()->create(['trip_id' => $trip->id]);

    $this->patchJson('/api/v1/trips/'.$trip->id.'/packing-items/'.$item->id, [
        'quantity' => -1,
    ])->assertUnprocessable()->assertJsonValidationErrors('quantity');
});

test('a trip member cannot access another trips packing item or participant', function (): void {
    $user = User::factory()->create();
    $trip = Trip::factory()->create();
    $otherTrip = Trip::factory()->create();
    $trip->members()->attach($user, ['role' => 'member']);
    $otherParticipant = TripParticipant::factory()->create(['trip_id' => $otherTrip->id]);
    $otherItem = PackingItem::factory()->create(['trip_id' => $otherTrip->id]);
    Passport::actingAs($user);

    $this->getJson('/api/v1/trips/'.$trip->id.'/packing-items/'.$otherItem->id)->assertNotFound();
    $this->patchJson('/api/v1/trips/'.$trip->id.'/packing-items/'.$otherItem->id, [
        'name' => 'Stolen item',
    ])->assertNotFound();
    $this->deleteJson('/api/v1/trips/'.$trip->id.'/packing-items/'.$otherItem->id)->assertNotFound();

    $this->postJson('/api/v1/trips/'.$trip->id.'/packing-items', [
        'name' => 'Shared item',
        'participant_id' => $otherParticipant->id,
    ])->assertNotFound();
});

test('a user without trip membership receives not found', function (): void {
    $trip = Trip::factory()->create();
    Passport::actingAs(User::factory()->create());

    $this->getJson('/api/v1/trips/'.$trip->id.'/packing-items')->assertNotFound();
});
