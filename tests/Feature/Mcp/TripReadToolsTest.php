<?php

use App\Mcp\Servers\TripServer;
use App\Mcp\Tools\ListTripsTool;
use App\Mcp\Tools\ShowPackingListsTool;
use App\Mcp\Tools\ShowTripTool;
use App\Models\PackingItem;
use App\Models\Trip;
use App\Models\TripParticipant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the trip server registers its three read-only tools', function (): void {
    TripServer::actingAs(User::factory()->create())
        ->tools()
        ->assertRegistered([ListTripsTool::class, ShowTripTool::class, ShowPackingListsTool::class]);
});

test('list trips includes only trips the authenticated user belongs to', function (): void {
    $user = User::factory()->create();
    $ownTrip = Trip::factory()->create(['destination' => 'Oslo']);
    $otherTrip = Trip::factory()->create(['destination' => 'Bergen']);
    $ownTrip->members()->attach($user, ['role' => 'member']);

    TripServer::actingAs($user)
        ->tool(ListTripsTool::class)
        ->assertOk()
        ->assertSee('Oslo')
        ->assertDontSee('Bergen');
});

test('show trip returns the selected member trip and hides unknown or other-user trips', function (): void {
    $user = User::factory()->create();
    $ownTrip = Trip::factory()->create(['destination' => 'Lisbon']);
    $otherTrip = Trip::factory()->create(['destination' => 'Reykjavik']);
    $ownTrip->members()->attach($user, ['role' => 'member']);

    TripServer::actingAs($user)
        ->tool(ShowTripTool::class, ['trip_id' => $ownTrip->id])
        ->assertOk()
        ->assertSee('Lisbon');

    TripServer::actingAs($user)
        ->tool(ShowTripTool::class, ['trip_id' => $otherTrip->id])
        ->assertHasErrors(['Trip not found.']);

    TripServer::actingAs($user)
        ->tool(ShowTripTool::class, ['trip_id' => 999999])
        ->assertHasErrors(['Trip not found.']);
});

test('packing list tool returns empty shared and participant lists', function (): void {
    $user = User::factory()->create();
    $trip = Trip::factory()->create();
    $trip->members()->attach($user, ['role' => 'member']);
    TripParticipant::factory()->create(['trip_id' => $trip->id, 'name' => 'Alex']);

    TripServer::actingAs($user)
        ->tool(ShowPackingListsTool::class, ['trip_id' => $trip->id])
        ->assertOk()
        ->assertSee('shared_items')
        ->assertSee('participant_lists')
        ->assertSee('Alex');
});

test('packing list tool separates shared and participant items and excludes other trips', function (): void {
    $user = User::factory()->create();
    $trip = Trip::factory()->create();
    $otherTrip = Trip::factory()->create();
    $trip->members()->attach($user, ['role' => 'member']);
    $participant = TripParticipant::factory()->create(['trip_id' => $trip->id, 'name' => 'Jordan']);
    PackingItem::factory()->create([
        'trip_id' => $trip->id,
        'participant_id' => null,
        'name' => 'Shared sunscreen',
    ]);
    PackingItem::factory()->create([
        'trip_id' => $trip->id,
        'participant_id' => $participant->id,
        'name' => 'Jordan jacket',
    ]);
    PackingItem::factory()->create([
        'trip_id' => $otherTrip->id,
        'name' => 'Private passport',
    ]);

    TripServer::actingAs($user)
        ->tool(ShowPackingListsTool::class, ['trip_id' => $trip->id])
        ->assertOk()
        ->assertSee('Shared sunscreen')
        ->assertSee('Jordan jacket')
        ->assertSee('Jordan')
        ->assertDontSee('Private passport');
});

test('packing list tool returns the same not-found result for trips outside membership', function (): void {
    $user = User::factory()->create();
    $otherTrip = Trip::factory()->create();

    TripServer::actingAs($user)
        ->tool(ShowPackingListsTool::class, ['trip_id' => $otherTrip->id])
        ->assertHasErrors(['Trip not found.']);
});
