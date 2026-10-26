<?php

use App\Mcp\Servers\TripServer;
use App\Mcp\Tools\SearchSimilarTripsTool;
use App\Models\PackingItem;
use App\Models\Trip;
use App\Models\TripInspirationItem;
use App\Models\TripInspirationProjection;
use App\Models\TripParticipant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->travelTo('2026-10-26 12:00:00');
});

function createMcpSearchProjection(User $owner): TripInspirationProjection
{
    $sourceTrip = Trip::factory()->create([
        'created_by_user_id' => $owner->id,
        'destination' => 'Private MCP source destination',
        'start_date' => '2026-10-01',
        'end_date' => '2026-10-03',
        'description' => 'Private MCP source description',
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
        'name' => 'Private MCP source person',
    ]);
    PackingItem::factory()->create([
        'trip_id' => $sourceTrip->id,
        'name' => 'Private MCP source gear',
        'comment' => 'private-mcp@example.com',
    ]);

    return $projection;
}

test('similar trips tool registers as authenticated read-only tool with a trip id schema', function (): void {
    TripServer::actingAs(User::factory()->create())
        ->tools()
        ->assertRegistered(SearchSimilarTripsTool::class);

    $tool = new SearchSimilarTripsTool;

    expect($tool->name())->toBe('search_similar_trips')
        ->and($tool->toArray()['inputSchema']['properties'])->toHaveKey('trip_id')
        ->and($tool->toArray()['annotations']['readOnlyHint'])->toBeTrue();
});

test('tool returns own and anonymous results without private source details', function (): void {
    $user = User::factory()->create();
    $trip = Trip::factory()->create([
        'start_date' => '2026-10-26',
        'end_date' => '2026-10-28',
        'type' => 'adventure',
        'transport' => 'plane',
        'accommodation' => 'hotel',
    ]);
    $trip->members()->attach($user, ['role' => 'member']);

    $pastTrip = Trip::factory()->create([
        'created_by_user_id' => $user->id,
        'destination' => 'Own past destination',
        'start_date' => '2026-10-01',
        'end_date' => '2026-10-03',
        'type' => 'adventure',
        'transport' => 'plane',
        'accommodation' => 'hotel',
        'activities' => ['hiking'],
    ]);
    $pastTrip->members()->attach($user, ['role' => 'member']);

    foreach (range(1, 3) as $index) {
        createMcpSearchProjection(User::factory()->create());
    }

    $response = TripServer::actingAs($user)
        ->tool(SearchSimilarTripsTool::class, ['trip_id' => $trip->id])
        ->assertOk()
        ->assertSee('own')
        ->assertSee('anonymous')
        ->assertSee('Own past destination')
        ->assertSee('Passport')
        ->assertDontSee([
            'Private MCP source destination',
            'Private MCP source description',
            'Private MCP source person',
            'Private MCP source gear',
            'private-mcp@example.com',
            'owner_id',
            'trip_id',
        ]);

    $response->assertStructuredContent(function ($json): void {
        $json->has('results', 2)
            ->where('results.0.source', 'own')
            ->where('results.1.source', 'anonymous')
            ->where('results.1.item_names.0', 'Passport')
            ->where('results.1.categories.duration_group', 'weekend');
    });
});

test('tool omits anonymous categories until there are three distinct owners', function (): void {
    $user = User::factory()->create();
    $trip = Trip::factory()->create();
    $trip->members()->attach($user, ['role' => 'member']);

    createMcpSearchProjection(User::factory()->create());
    createMcpSearchProjection(User::factory()->create());

    TripServer::actingAs($user)
        ->tool(SearchSimilarTripsTool::class, ['trip_id' => $trip->id])
        ->assertOk()
        ->assertStructuredContent(['results' => []]);
});

test('tool uses the same not-found error for a trip outside membership and an unknown id', function (): void {
    $user = User::factory()->create();
    $otherTrip = Trip::factory()->create(['destination' => 'Private other trip']);

    TripServer::actingAs($user)
        ->tool(SearchSimilarTripsTool::class, ['trip_id' => $otherTrip->id])
        ->assertHasErrors(['Trip not found.'])
        ->assertDontSee('Private other trip');

    TripServer::actingAs($user)
        ->tool(SearchSimilarTripsTool::class, ['trip_id' => 999999])
        ->assertHasErrors(['Trip not found.']);
});

test('tool returns no more than ten results', function (): void {
    $user = User::factory()->create();
    $trip = Trip::factory()->create();
    $trip->members()->attach($user, ['role' => 'member']);

    foreach (range(1, 12) as $index) {
        $pastTrip = Trip::factory()->create([
            'created_by_user_id' => $user->id,
            'destination' => 'Past MCP destination '.$index,
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-03',
        ]);
        $pastTrip->members()->attach($user, ['role' => 'member']);
    }

    TripServer::actingAs($user)
        ->tool(SearchSimilarTripsTool::class, ['trip_id' => $trip->id])
        ->assertOk()
        ->assertStructuredContent(fn ($json) => $json->has('results', 10));
});
