<?php

use App\Mcp\Servers\TripServer;
use App\Mcp\Tools\CreateTripTool;
use App\Mcp\Tools\UpdateTripTool;
use App\Models\Trip;
use App\Models\TripInspirationItem;
use App\Models\TripInspirationProjection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->travelTo('2026-10-26 12:00:00');
});

test('trip write tools are registered for authenticated users', function (): void {
    TripServer::actingAs(User::factory()->create())
        ->tools()
        ->assertRegistered([CreateTripTool::class, UpdateTripTool::class]);

    expect((new CreateTripTool)->name())->toBe('create_trip')
        ->and((new UpdateTripTool)->name())->toBe('update_trip');
});

test('create trip tool creates a trip and makes the caller an admin', function (): void {
    $user = User::factory()->create();

    TripServer::actingAs($user)
        ->tool(CreateTripTool::class, [
            'destination' => 'Oslo',
            'start_date' => '2026-11-02',
            'end_date' => '2026-11-02',
            'type' => 'family',
            'transport' => 'train',
            'accommodation' => 'cabin',
            'activities' => ['hiking'],
            'description' => 'Weekend plans',
        ])
        ->assertOk()
        ->assertSee('Oslo')
        ->assertStructuredContent(function ($json): void {
            $json->where('trip.destination', 'Oslo')
                ->where('trip.duration_days', 1)
                ->where('trip.activities.0', 'hiking');
        });

    $trip = Trip::query()->where('destination', 'Oslo')->firstOrFail();

    $this->assertDatabaseHas('trip_user', [
        'trip_id' => $trip->id,
        'user_id' => $user->id,
        'role' => 'admin',
    ]);
});

test('create trip tool returns validation errors for invalid catalog and dates', function (): void {
    $user = User::factory()->create();

    TripServer::actingAs($user)
        ->tool(CreateTripTool::class, [
            'destination' => 'Oslo',
            'start_date' => '2026-11-03',
            'end_date' => '2026-11-02',
            'type' => 'private',
            'transport' => 'train',
            'accommodation' => 'cabin',
            'activities' => ['secret activity'],
        ])
        ->assertHasErrors();

    $this->assertDatabaseCount('trips', 0);
});

test('a member can partially update a trip and refresh its inspiration projection', function (): void {
    $member = User::factory()->create();
    $trip = Trip::factory()->create([
        'destination' => 'Oslo',
        'start_date' => '2026-10-01',
        'end_date' => '2026-10-03',
        'type' => 'adventure',
        'transport' => 'plane',
        'accommodation' => 'hotel',
        'activities' => ['hiking'],
        'description' => 'Keep these notes',
    ]);
    $trip->members()->attach($member, ['role' => 'member']);
    $projection = TripInspirationProjection::factory()->create([
        'trip_id' => $trip->id,
        'owner_id' => $trip->created_by_user_id,
    ]);
    $projectionItem = TripInspirationItem::factory()->create(['projection_id' => $projection->id]);

    TripServer::actingAs($member)
        ->tool(UpdateTripTool::class, [
            'trip_id' => $trip->id,
            'destination' => 'Bergen',
            'end_date' => '2026-10-30',
        ])
        ->assertOk()
        ->assertStructuredContent(function ($json): void {
            $json->where('trip.destination', 'Bergen')
                ->where('trip.start_date', '2026-10-01')
                ->where('trip.type', 'adventure')
                ->where('trip.activities.0', 'hiking')
                ->where('trip.description', 'Keep these notes');
        });

    $this->assertModelMissing($projection);
    $this->assertModelMissing($projectionItem);
    $this->assertDatabaseHas('trips', [
        'id' => $trip->id,
        'destination' => 'Bergen',
    ]);
    expect($trip->fresh()->end_date->toDateString())->toBe('2026-10-30');
});

test('update trip tool returns validation errors for invalid values', function (): void {
    $member = User::factory()->create();
    $trip = Trip::factory()->create([
        'start_date' => '2026-11-02',
        'end_date' => '2026-11-04',
    ]);
    $trip->members()->attach($member, ['role' => 'member']);

    TripServer::actingAs($member)
        ->tool(UpdateTripTool::class, [
            'trip_id' => $trip->id,
            'type' => 'private',
            'activities' => ['secret activity'],
            'start_date' => '2026-11-05',
        ])
        ->assertHasErrors();

    $this->assertDatabaseHas('trips', [
        'id' => $trip->id,
        'type' => $trip->type,
    ]);
    expect($trip->fresh()->start_date->toDateString())->toBe('2026-11-02');
});

test('update trip tool hides whether an inaccessible trip exists', function (): void {
    $user = User::factory()->create();
    $otherTrip = Trip::factory()->create(['destination' => 'Private destination']);

    TripServer::actingAs($user)
        ->tool(UpdateTripTool::class, [
            'trip_id' => $otherTrip->id,
            'destination' => 'Attempted access',
        ])
        ->assertHasErrors(['Trip not found.'])
        ->assertDontSee('Private destination');

    TripServer::actingAs($user)
        ->tool(UpdateTripTool::class, [
            'trip_id' => 999999,
            'destination' => 'Attempted access',
        ])
        ->assertHasErrors(['Trip not found.']);

    $this->assertModelExists($otherTrip);
});
