<?php

use App\Models\Trip;
use App\Models\TripParticipant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;

uses(RefreshDatabase::class);

test('participant routes return 401 without a passport token', function (): void {
    $this->getJson('/api/v1/trips/1/participants')->assertUnauthorized();
});

test('a trip member can read an empty participant list', function (): void {
    $user = User::factory()->create();
    $trip = Trip::factory()->create();
    $trip->members()->attach($user, ['role' => 'member']);
    Passport::actingAs($user);

    $this->getJson('/api/v1/trips/'.$trip->id.'/participants')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

test('a trip member can create update and delete participants', function (): void {
    $user = User::factory()->create();
    $participantAccount = User::factory()->create();
    $trip = Trip::factory()->create();
    $trip->members()->attach($user, ['role' => 'member']);
    $trip->members()->attach($participantAccount, ['role' => 'member']);
    Passport::actingAs($user);

    $storeResponse = $this->postJson('/api/v1/trips/'.$trip->id.'/participants', [
        'name' => 'Alex Example',
        'age' => 0,
        'user_id' => $participantAccount->id,
    ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Alex Example')
        ->assertJsonPath('data.age', 0);

    $participantId = $storeResponse->json('data.id');

    $this->assertDatabaseHas('trip_participants', [
        'id' => $participantId,
        'trip_id' => $trip->id,
        'user_id' => $participantAccount->id,
    ]);

    $this->patchJson('/api/v1/trips/'.$trip->id.'/participants/'.$participantId, [
        'name' => 'Alex Updated',
        'age' => null,
    ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Alex Updated')
        ->assertJsonPath('data.age', null);

    $this->deleteJson('/api/v1/trips/'.$trip->id.'/participants/'.$participantId)
        ->assertNoContent();

    $this->assertDatabaseMissing('trip_participants', ['id' => $participantId]);
});

test('participant creation returns 422 for invalid fields and nonmember accounts', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $trip = Trip::factory()->create();
    $trip->members()->attach($user, ['role' => 'member']);
    Passport::actingAs($user);

    $this->postJson('/api/v1/trips/'.$trip->id.'/participants', [
        'name' => '',
        'age' => 121,
        'user_id' => $otherUser->id,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'age', 'user_id']);

    $this->assertDatabaseCount('trip_participants', 0);
});

test('a trip member receives 404 for another trip participant', function (): void {
    $user = User::factory()->create();
    $trip = Trip::factory()->create();
    $otherTrip = Trip::factory()->create();
    $trip->members()->attach($user, ['role' => 'member']);
    $otherParticipant = TripParticipant::factory()->create(['trip_id' => $otherTrip->id]);
    Passport::actingAs($user);

    $this->getJson('/api/v1/trips/'.$trip->id.'/participants/'.$otherParticipant->id)
        ->assertNotFound();
});

test('a user receives 404 when selecting a trip without membership', function (): void {
    $trip = Trip::factory()->create();
    Passport::actingAs(User::factory()->create());

    $this->getJson('/api/v1/trips/'.$trip->id.'/participants')->assertNotFound();
});

test('a trip member with an unauthorized role cannot delete a participant', function (): void {
    $user = User::factory()->create();
    $trip = Trip::factory()->create();
    $trip->members()->attach($user, ['role' => 'guest']);
    $participant = TripParticipant::factory()->create(['trip_id' => $trip->id]);
    Passport::actingAs($user);

    $this->deleteJson('/api/v1/trips/'.$trip->id.'/participants/'.$participant->id)
        ->assertForbidden();

    $this->assertDatabaseHas('trip_participants', ['id' => $participant->id]);
});
