<?php

use App\Models\PackingItem;
use App\Models\Trip;
use App\Models\TripParticipant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('redirects guests to sign in from the trip index', function () {
    $this->get(route('trips.index'))
        ->assertRedirect(route('login'));
});

it('requires a verified email before opening trip pages', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get(route('trips.index'))
        ->assertRedirect(route('verification.notice'));
});

it('shows an empty state when the user has no trips', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('trips.index'))
        ->assertOk()
        ->assertSee('Your next trip starts here')
        ->assertSee('Plan your first trip');
});

it('shows only trips the signed-in user belongs to', function () {
    $user = User::factory()->create();
    $memberTrip = Trip::factory()->create(['destination' => 'Bergen']);
    $otherTrip = Trip::factory()->create(['destination' => 'Lisbon']);
    $memberTrip->members()->attach($user, ['role' => 'member']);

    $this->actingAs($user)
        ->get(route('trips.index'))
        ->assertOk()
        ->assertSee('Bergen')
        ->assertDontSee('Lisbon');
});

it('shows trip metadata and shared and participant packing lists to a member', function () {
    $user = User::factory()->create();
    $trip = Trip::factory()->create([
        'destination' => 'Reykjavik',
        'start_date' => '2026-10-25',
        'end_date' => '2026-10-25',
        'activities' => ['museums'],
    ]);
    $trip->members()->attach($user, ['role' => 'member']);
    $participant = TripParticipant::factory()->for($trip)->create(['name' => 'Alex']);
    PackingItem::factory()->for($trip)->create(['name' => 'Shared passport', 'quantity' => 2]);
    PackingItem::factory()->for($trip)->create([
        'participant_id' => $participant->getKey(),
        'name' => 'Alex’s coat',
    ]);

    $this->actingAs($user)
        ->get(route('trips.show', $trip))
        ->assertOk()
        ->assertSee('Reykjavik')
        ->assertSee('1 day')
        ->assertSee('Museums')
        ->assertSee('Shared passport')
        ->assertSee('Alex’s coat');
});

it('returns not found for trips outside the signed-in user’s memberships', function () {
    $user = User::factory()->create();
    $trip = Trip::factory()->create();

    $this->actingAs($user)
        ->get(route('trips.show', $trip))
        ->assertNotFound();
});

it('returns validation errors when a trip uses an unknown catalog value', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->followingRedirects()
        ->from(route('trips.create'))
        ->post(route('trips.store'), [
            'destination' => 'Bergen',
            'start_date' => '2026-11-02',
            'end_date' => '2026-11-02',
            'type' => 'unknown',
            'transport' => 'train',
            'accommodation' => 'hotel',
            'activities' => ['hiking', 'unknown'],
        ])
        ->assertOk()
        ->assertSee('Please review the highlighted fields and try again.');

    $this->actingAs($user)
        ->postJson(route('trips.store'), [
            'destination' => 'Bergen',
            'start_date' => '2026-11-02',
            'end_date' => '2026-11-02',
            'type' => 'unknown',
            'transport' => 'train',
            'accommodation' => 'hotel',
            'activities' => ['unknown'],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['type', 'activities.0']);

    $this->assertDatabaseCount('trips', 0);
});

it('creates a one-day trip with the creator as its admin', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('trips.store'), [
        'destination' => 'Bergen',
        'start_date' => '2026-10-25',
        'end_date' => '2026-10-25',
        'type' => 'family',
        'transport' => 'train',
        'accommodation' => 'hotel',
        'activities' => ['hiking'],
        'description' => 'A day trip',
    ]);

    $trip = Trip::query()->firstOrFail();

    $response->assertRedirect(route('trips.show', $trip));
    $this->assertDatabaseHas('trip_user', [
        'trip_id' => $trip->getKey(),
        'user_id' => $user->getKey(),
        'role' => 'admin',
    ]);
    expect($trip->durationInDays())->toBe(1);
});
