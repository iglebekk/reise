<?php

use App\Models\PackingItem;
use App\Models\Trip;
use App\Models\TripInspirationItem;
use App\Models\TripInspirationProjection;
use App\Models\TripParticipant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->travelTo('2026-10-26 12:00:00');
});

test('similar trips require authentication', function (): void {
    $this->getJson('/api/v1/trips/1/similar')->assertUnauthorized();
});

test('a user without membership receives not found for similar trips', function (): void {
    $trip = Trip::factory()->create();
    Passport::actingAs(User::factory()->create());

    $this->getJson('/api/v1/trips/'.$trip->id.'/similar')->assertNotFound();
});

test('similar trips include own history and anonymous results with distinct source labels', function (): void {
    $user = User::factory()->create();
    $trip = Trip::factory()->create([
        'start_date' => '2026-10-26',
        'end_date' => '2026-10-28',
        'type' => 'adventure',
        'transport' => 'plane',
        'accommodation' => 'hotel',
        'activities' => ['hiking'],
    ]);
    $trip->members()->attach($user, ['role' => 'member']);

    $pastTrip = Trip::factory()->create([
        'created_by_user_id' => $user->id,
        'destination' => 'Private Oslo destination',
        'start_date' => '2026-10-01',
        'end_date' => '2026-10-03',
        'type' => 'adventure',
        'activities' => ['hiking'],
        'description' => 'Private own-trip details',
    ]);
    $pastTrip->members()->attach($user, ['role' => 'admin']);
    $participant = TripParticipant::factory()->create([
        'trip_id' => $pastTrip->id,
        'name' => 'Own Participant',
        'age' => 39,
    ]);
    PackingItem::factory()->create([
        'trip_id' => $pastTrip->id,
        'participant_id' => $participant->id,
        'name' => 'Own packing item',
        'comment' => 'Own private comment',
    ]);

    foreach (range(1, 3) as $index) {
        $owner = User::factory()->create();
        $sourceTrip = Trip::factory()->create([
            'created_by_user_id' => $owner->id,
            'destination' => 'Private source destination '.$index,
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-03',
            'description' => 'Private source description '.$index,
            'type' => 'adventure',
            'transport' => 'plane',
            'accommodation' => 'hotel',
        ]);
        $projection = TripInspirationProjection::factory()->create([
            'trip_id' => $sourceTrip->id,
            'owner_id' => $owner->id,
            'trip_type' => 'adventure',
            'transport' => 'plane',
            'accommodation' => 'hotel',
            'duration_group' => 'weekend',
        ]);
        TripInspirationItem::factory()->create([
            'projection_id' => $projection->id,
            'canonical_name' => 'Passport',
        ]);
        TripParticipant::factory()->create([
            'trip_id' => $sourceTrip->id,
            'name' => 'Private source person '.$index,
        ]);
        PackingItem::factory()->create([
            'trip_id' => $sourceTrip->id,
            'name' => 'Private source gear '.$index,
            'comment' => 'private@example.com',
        ]);
    }

    Passport::actingAs($user);
    $response = $this->getJson('/api/v1/trips/'.$trip->id.'/similar')->assertOk();
    $results = collect($response->json('data'));
    $own = $results->firstWhere('source', 'own');
    $anonymous = $results->firstWhere('source', 'anonymous');
    $anonymousJson = json_encode($anonymous, JSON_THROW_ON_ERROR);

    expect($results->pluck('source')->all())->toContain('own', 'anonymous')
        ->and($own['trip']['description'])->toBe('Private own-trip details')
        ->and($own['trip']['participants'][0]['name'])->toBe('Own Participant')
        ->and($own['trip']['participants'][0]['packing_items'][0]['comment'])->toBe('Own private comment')
        ->and(array_keys($anonymous))->toBe(['source', 'categories', 'item_names'])
        ->and($anonymous['item_names'])->toBe(['Passport'])
        ->and($anonymousJson)->not->toContain(
            'trip_id',
            'owner_id',
            'Private source destination',
            'Private source description',
            'Private source person',
            'Private source gear',
            'private@example.com',
        );
});

test('similar trips hides anonymous groups below the three-owner threshold', function (): void {
    $user = User::factory()->create();
    $trip = Trip::factory()->create([
        'start_date' => '2026-10-26',
        'end_date' => '2026-10-28',
        'type' => 'adventure',
        'transport' => 'plane',
        'accommodation' => 'hotel',
    ]);
    $trip->members()->attach($user, ['role' => 'member']);

    foreach (range(1, 2) as $index) {
        $owner = User::factory()->create();
        $sourceTrip = Trip::factory()->create([
            'created_by_user_id' => $owner->id,
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-03',
        ]);
        TripInspirationProjection::factory()->create([
            'trip_id' => $sourceTrip->id,
            'owner_id' => $owner->id,
            'trip_type' => 'adventure',
            'transport' => 'plane',
            'accommodation' => 'hotel',
            'duration_group' => 'weekend',
        ]);
    }

    Passport::actingAs($user);

    $this->getJson('/api/v1/trips/'.$trip->id.'/similar')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

test('similar trips returns at most ten results', function (): void {
    $user = User::factory()->create();
    $trip = Trip::factory()->create([
        'start_date' => '2026-10-26',
        'end_date' => '2026-10-28',
    ]);
    $trip->members()->attach($user, ['role' => 'member']);

    foreach (range(1, 12) as $index) {
        $pastTrip = Trip::factory()->create([
            'created_by_user_id' => $user->id,
            'destination' => 'Past destination '.$index,
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-03',
        ]);
        $pastTrip->members()->attach($user, ['role' => 'member']);
    }

    Passport::actingAs($user);

    $this->getJson('/api/v1/trips/'.$trip->id.'/similar')
        ->assertOk()
        ->assertJsonCount(10, 'data');
});
