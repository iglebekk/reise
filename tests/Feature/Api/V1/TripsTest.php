<?php

use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;

uses(RefreshDatabase::class);

test('the trip api returns 401 without a passport token', function (): void {
    $this->getJson('/api/v1/trips')->assertUnauthorized();
});

test('a user can list only trips they belong to', function (): void {
    $user = User::factory()->create();
    $memberTrip = Trip::factory()->create();
    $otherTrip = Trip::factory()->create();
    $memberTrip->members()->attach($user, ['role' => 'member']);

    Passport::actingAs($user);

    $this->getJson('/api/v1/trips')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $memberTrip->id)
        ->assertJsonMissing(['id' => $otherTrip->id]);
});

test('a user can create a trip and becomes its admin', function (): void {
    $user = User::factory()->create();
    Passport::actingAs($user);

    $this->postJson('/api/v1/trips', [
        'destination' => 'Oslo',
        'start_date' => '2026-11-02',
        'end_date' => '2026-11-02',
        'type' => 'family',
        'transport' => 'train',
        'accommodation' => 'cabin',
        'activities' => ['hiking'],
        'description' => null,
    ])
        ->assertCreated()
        ->assertJsonPath('data.destination', 'Oslo')
        ->assertJsonPath('data.duration_days', 1)
        ->assertJsonMissingPath('data.created_by_user_id');

    $trip = Trip::query()->where('destination', 'Oslo')->firstOrFail();

    $this->assertDatabaseHas('trip_user', [
        'trip_id' => $trip->id,
        'user_id' => $user->id,
        'role' => 'admin',
    ]);
});

test('trip creation returns 422 for values outside the catalog', function (): void {
    Passport::actingAs(User::factory()->create());

    $this->postJson('/api/v1/trips', [
        'destination' => 'Oslo',
        'start_date' => '2026-11-02',
        'end_date' => '2026-11-02',
        'type' => 'private',
        'transport' => 'train',
        'accommodation' => 'cabin',
        'activities' => ['secret meetup'],
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['type', 'activities.0']);

    $this->assertDatabaseCount('trips', 0);
});

test('a user can read a trip through their membership', function (): void {
    $user = User::factory()->create();
    $trip = Trip::factory()->create();
    $trip->members()->attach($user, ['role' => 'member']);
    Passport::actingAs($user);

    $this->getJson('/api/v1/trips/'.$trip->id)
        ->assertOk()
        ->assertJsonPath('data.id', $trip->id)
        ->assertJsonPath('data.destination', $trip->destination);
});

test('a user receives 404 for a trip without membership', function (): void {
    $trip = Trip::factory()->create();
    Passport::actingAs(User::factory()->create());

    $this->getJson('/api/v1/trips/'.$trip->id)->assertNotFound();
});

test('a user receives 404 for an unknown trip id', function (): void {
    Passport::actingAs(User::factory()->create());

    $this->getJson('/api/v1/trips/999999')->assertNotFound();
});
