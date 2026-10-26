<?php

use App\Models\Trip;
use App\Models\TripParticipant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('redirects the root route to the trips page', function () {
    $this->get('/')
        ->assertRedirect(route('trips.index'));
});

it('shows an empty participant list and add form to a trip member', function () {
    $user = User::factory()->create();
    $trip = Trip::factory()->create();
    $trip->members()->attach($user, ['role' => 'member']);

    $this->actingAs($user)
        ->get(route('trips.show', $trip))
        ->assertOk()
        ->assertSee('No participants added yet.')
        ->assertSee('Add participant');
});

it('lets a trip member add, update, and remove a participant', function () {
    $user = User::factory()->create();
    $linkedMember = User::factory()->create(['name' => 'Linked member']);
    $trip = Trip::factory()->create();
    $trip->members()->attach($user, ['role' => 'admin']);
    $trip->members()->attach($linkedMember, ['role' => 'member']);

    $createResponse = $this->actingAs($user)->post(route('trips.participants.store', $trip), [
        'name' => 'Jordan',
        'age' => 12,
        'user_id' => $linkedMember->getKey(),
    ]);
    $participant = TripParticipant::query()->where('trip_id', $trip->getKey())->firstOrFail();

    $createResponse->assertRedirect(route('trips.show', $trip));
    expect($participant->name)->toBe('Jordan')
        ->and($participant->age)->toBe(12)
        ->and($participant->user_id)->toBe($linkedMember->getKey());

    $this->patch(route('trips.participants.update', [$trip, $participant]), [
        'name' => 'Jordan Lee',
        'age' => 13,
        'user_id' => null,
    ])
        ->assertRedirect(route('trips.show', $trip));

    $this->assertDatabaseHas('trip_participants', [
        'id' => $participant->getKey(),
        'name' => 'Jordan Lee',
        'age' => 13,
        'user_id' => null,
    ]);

    $this->delete(route('trips.participants.destroy', [$trip, $participant]))
        ->assertRedirect(route('trips.show', $trip));

    $this->assertDatabaseMissing('trip_participants', ['id' => $participant->getKey()]);
});

it('returns validation errors for invalid age and a user outside the trip', function () {
    $user = User::factory()->create();
    $outsideMember = User::factory()->create();
    $trip = Trip::factory()->create();
    $trip->members()->attach($user, ['role' => 'admin']);

    $this->actingAs($user)
        ->postJson(route('trips.participants.store', $trip), [
            'name' => 'Jordan',
            'age' => 121,
            'user_id' => $outsideMember->getKey(),
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['age', 'user_id']);

    $this->assertDatabaseCount('trip_participants', 0);
});

it('returns not found when a participant belongs to another trip', function () {
    $user = User::factory()->create();
    $trip = Trip::factory()->create();
    $otherTrip = Trip::factory()->create();
    $trip->members()->attach($user, ['role' => 'member']);
    $participant = TripParticipant::factory()->for($otherTrip)->create();

    $this->actingAs($user)
        ->patch(route('trips.participants.update', [$trip, $participant]), ['name' => 'Changed'])
        ->assertNotFound();
});

it('requires authentication and verified email for participant routes', function () {
    $trip = Trip::factory()->create();
    $this->post(route('trips.participants.store', $trip), ['name' => 'Jordan'])
        ->assertRedirect(route('login'));

    $unverifiedUser = User::factory()->unverified()->create();
    $trip->members()->attach($unverifiedUser, ['role' => 'admin']);

    $this->actingAs($unverifiedUser)
        ->post(route('trips.participants.store', $trip), ['name' => 'Jordan'])
        ->assertRedirect(route('verification.notice'));
});
