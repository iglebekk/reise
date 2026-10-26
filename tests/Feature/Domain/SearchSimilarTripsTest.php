<?php

use App\Actions\RefreshTripInspirationProjection;
use App\Models\PackingItem;
use App\Models\Trip;
use App\Models\TripInspirationItem;
use App\Models\TripInspirationProjection;
use App\Models\TripParticipant;
use App\Models\User;
use App\Services\SearchSimilarTrips;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->travelTo('2026-10-26 12:00:00');
});

function createPastProjectionForSearchSimilarTrips(User $owner, array $overrides = []): TripInspirationProjection
{
    $trip = Trip::factory()->create([
        'created_by_user_id' => $owner->id,
        'start_date' => '2026-10-01',
        'end_date' => '2026-10-03',
        'type' => 'adventure',
        'transport' => 'plane',
        'accommodation' => 'hotel',
        ...$overrides,
    ]);

    return TripInspirationProjection::factory()->create([
        'trip_id' => $trip->id,
        'owner_id' => $owner->id,
        'trip_type' => $trip->type,
        'transport' => $trip->transport,
        'accommodation' => $trip->accommodation,
        'duration_group' => 'weekend',
    ]);
}

function selectTripForSearchSimilarTrips(User $user, Trip $trip): void
{
    $trip->members()->attach($user, ['role' => 'member']);
    $user->setCurrentTenant($trip);
}

test('search returns the users past trip with participant and packing item details', function (): void {
    $user = User::factory()->create();
    $selectedTrip = Trip::factory()->create([
        'start_date' => '2026-10-26',
        'end_date' => '2026-10-28',
        'type' => 'adventure',
        'activities' => ['hiking', 'camping'],
    ]);
    selectTripForSearchSimilarTrips($user, $selectedTrip);

    $pastTrip = Trip::factory()->create([
        'created_by_user_id' => $user->id,
        'destination' => 'Oslo',
        'start_date' => '2026-10-01',
        'end_date' => '2026-10-03',
        'type' => 'adventure',
        'transport' => 'plane',
        'accommodation' => 'hotel',
        'activities' => ['hiking'],
        'description' => 'Private past trip notes',
    ]);
    $pastTrip->members()->attach($user, ['role' => 'admin']);
    $participant = TripParticipant::factory()->create([
        'trip_id' => $pastTrip->id,
        'name' => 'Private Participant',
        'age' => 42,
    ]);
    PackingItem::factory()->create([
        'trip_id' => $pastTrip->id,
        'participant_id' => $participant->id,
        'name' => 'Prescription details',
        'comment' => 'Private medicine note',
    ]);

    $results = app(SearchSimilarTrips::class)->handle($user);
    $ownResult = collect($results)->firstWhere('source', 'own');

    expect($ownResult['trip']['id'])->toBe($pastTrip->id)
        ->and($ownResult['trip']['description'])->toBe('Private past trip notes')
        ->and($ownResult['trip']['participants'][0]['name'])->toBe('Private Participant')
        ->and($ownResult['trip']['participants'][0]['packing_items'][0]['comment'])->toBe('Private medicine note');
});

test('anonymous groups stay hidden until three distinct owners contribute', function (): void {
    $user = User::factory()->create();
    $selectedTrip = Trip::factory()->create([
        'start_date' => '2026-10-26',
        'end_date' => '2026-10-28',
        'type' => 'adventure',
        'transport' => 'plane',
        'accommodation' => 'hotel',
    ]);
    selectTripForSearchSimilarTrips($user, $selectedTrip);

    createPastProjectionForSearchSimilarTrips(User::factory()->create());
    createPastProjectionForSearchSimilarTrips(User::factory()->create());

    expect(collect(app(SearchSimilarTrips::class)->handle($user))->where('source', 'anonymous'))->toBeEmpty();

    createPastProjectionForSearchSimilarTrips(User::factory()->create());
    $anonymousResults = collect(app(SearchSimilarTrips::class)->handle($user))->where('source', 'anonymous')->values();

    expect($anonymousResults)->toHaveCount(1)
        ->and($anonymousResults[0])->toHaveKeys(['source', 'categories', 'item_names'])
        ->and($anonymousResults[0]['source'])->toBe('anonymous')
        ->and($anonymousResults[0]['categories'])->toBe([
            'type' => 'adventure',
            'transport' => 'plane',
            'accommodation' => 'hotel',
            'duration_group' => 'weekend',
        ]);
});

test('anonymous results include only canonical names and coarse categories', function (): void {
    $user = User::factory()->create();
    $selectedTrip = Trip::factory()->create([
        'start_date' => '2026-10-26',
        'end_date' => '2026-10-28',
        'type' => 'adventure',
        'transport' => 'plane',
        'accommodation' => 'hotel',
    ]);
    selectTripForSearchSimilarTrips($user, $selectedTrip);

    $projections = collect(range(1, 3))->map(function (): TripInspirationProjection {
        return createPastProjectionForSearchSimilarTrips(User::factory()->create());
    });

    foreach ($projections as $projection) {
        TripInspirationItem::factory()->create([
            'projection_id' => $projection->id,
            'canonical_name' => 'Passport',
        ]);
        TripInspirationItem::factory()->create([
            'projection_id' => $projection->id,
            'canonical_name' => 'Phone',
        ]);
    }

    $anonymous = collect(app(SearchSimilarTrips::class)->handle($user))->firstWhere('source', 'anonymous');
    $encoded = json_encode($anonymous, JSON_THROW_ON_ERROR);

    expect($anonymous['item_names'])->toBe(['Passport', 'Phone'])
        ->and(array_keys($anonymous))->toBe(['source', 'categories', 'item_names'])
        ->and($encoded)->not->toContain('owner_id', 'trip_id', 'count', 'destination', 'comment');
});

test('future trips do not enter anonymous results before refresh creates a projection', function (): void {
    $user = User::factory()->create();
    $selectedTrip = Trip::factory()->create([
        'start_date' => '2026-10-26',
        'end_date' => '2026-10-28',
        'type' => 'adventure',
        'transport' => 'plane',
        'accommodation' => 'hotel',
    ]);
    selectTripForSearchSimilarTrips($user, $selectedTrip);

    createPastProjectionForSearchSimilarTrips(User::factory()->create());
    createPastProjectionForSearchSimilarTrips(User::factory()->create());

    $futureOwner = User::factory()->create();
    $futureTrip = Trip::factory()->create([
        'created_by_user_id' => $futureOwner->id,
        'start_date' => '2026-10-27',
        'end_date' => '2026-10-29',
        'type' => 'adventure',
        'transport' => 'plane',
        'accommodation' => 'hotel',
    ]);

    expect(app(RefreshTripInspirationProjection::class)->handle($futureTrip))->toBeNull()
        ->and($futureTrip->inspirationProjection()->exists())->toBeFalse()
        ->and(collect(app(SearchSimilarTrips::class)->handle($user))->where('source', 'anonymous'))->toBeEmpty();
});

test('search excludes current and membership trips from anonymous sources', function (): void {
    $user = User::factory()->create();
    $selectedTrip = Trip::factory()->create([
        'start_date' => '2026-10-26',
        'end_date' => '2026-10-28',
        'type' => 'adventure',
        'transport' => 'plane',
        'accommodation' => 'hotel',
    ]);
    selectTripForSearchSimilarTrips($user, $selectedTrip);

    $memberOwnedTrip = Trip::factory()->create([
        'created_by_user_id' => $user->id,
        'start_date' => '2026-10-01',
        'end_date' => '2026-10-03',
    ]);
    $memberOwnedTrip->members()->attach($user, ['role' => 'member']);
    $ownProjection = TripInspirationProjection::factory()->create([
        'trip_id' => $memberOwnedTrip->id,
        'owner_id' => $user->id,
    ]);

    $otherMember = User::factory()->create();
    $sharedTrip = Trip::factory()->create([
        'created_by_user_id' => $otherMember->id,
        'start_date' => '2026-10-01',
        'end_date' => '2026-10-03',
    ]);
    $sharedTrip->members()->attach($user, ['role' => 'member']);
    TripInspirationProjection::factory()->create([
        'trip_id' => $sharedTrip->id,
        'owner_id' => $otherMember->id,
    ]);

    expect(collect(app(SearchSimilarTrips::class)->handle($user))->where('source', 'anonymous'))->toBeEmpty()
        ->and($selectedTrip->inspirationProjection()->exists())->toBeFalse()
        ->and($ownProjection->exists)->toBeTrue();
});

test('search returns no more than ten results', function (): void {
    $user = User::factory()->create();
    $selectedTrip = Trip::factory()->create([
        'start_date' => '2026-10-26',
        'end_date' => '2026-10-28',
    ]);
    selectTripForSearchSimilarTrips($user, $selectedTrip);

    foreach (range(1, 12) as $index) {
        $pastTrip = Trip::factory()->create([
            'created_by_user_id' => $user->id,
            'destination' => 'Past destination '.$index,
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-03',
        ]);
        $pastTrip->members()->attach($user, ['role' => 'member']);
    }

    expect(app(SearchSimilarTrips::class)->handle($user))->toHaveCount(10);
});
