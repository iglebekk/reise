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
use Laravel\Passport\Passport;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->travelTo('2026-10-26 12:00:00');
});

it('shows an accountless participant with an empty list and calculates a one-day trip across interfaces', function () {
    $owner = User::factory()->create();
    $trip = Trip::factory()->for($owner, 'creator')->create([
        'start_date' => '2026-10-26',
        'end_date' => '2026-10-26',
        'activities' => [],
    ]);
    $trip->members()->attach($owner, ['role' => 'admin']);
    $participant = TripParticipant::factory()->for($trip)->create([
        'user_id' => null,
        'name' => 'Accountless Child',
        'age' => 8,
    ]);

    expect($participant->user)->toBeNull()
        ->and(User::query()->count())->toBe(1)
        ->and($trip->durationInDays())->toBe(1);

    $this->actingAs($owner)
        ->get(route('trips.show', $trip))
        ->assertOk()
        ->assertSeeText('1 day')
        ->assertSeeText('Accountless Child')
        ->assertSeeText(__('trips.no_items'));

    Passport::actingAs($owner);

    $this->getJson('/api/v1/trips/'.$trip->getKey())
        ->assertOk()
        ->assertJsonPath('data.duration_days', 1);
    $this->getJson('/api/v1/trips/'.$trip->getKey().'/participants')
        ->assertOk()
        ->assertJsonPath('data.0.name', 'Accountless Child')
        ->assertJsonPath('data.0.age', 8);
    $this->getJson('/api/v1/trips/'.$trip->getKey().'/packing-items')
        ->assertOk()
        ->assertJsonCount(0, 'data');

    TripServer::actingAs($owner)
        ->tool(ShowTripTool::class, ['trip_id' => $trip->getKey()])
        ->assertOk()
        ->assertStructuredContent(fn ($json) => $json->where('trip.duration_days', 1)->etc());

    TripServer::actingAs($owner)
        ->tool(ShowPackingListsTool::class, ['trip_id' => $trip->getKey()])
        ->assertOk()
        ->assertStructuredContent([
            'trip_id' => $trip->getKey(),
            'shared_items' => [],
            'participant_lists' => [[
                'participant_id' => $participant->getKey(),
                'participant_name' => 'Accountless Child',
                'items' => [],
            ]],
        ]);
});

it('returns indistinguishable not-found results for an outsider and an unknown trip id', function () {
    $outsider = User::factory()->create();
    $trip = Trip::factory()->create(['destination' => 'Private Existing Destination']);
    $unknownTripId = 999999;

    $this->actingAs($outsider)
        ->get(route('trips.show', $trip))
        ->assertNotFound();
    $this->get('/trips/'.$unknownTripId)->assertNotFound();

    Passport::actingAs($outsider);
    $this->getJson('/api/v1/trips/'.$trip->getKey())->assertNotFound();
    $this->getJson('/api/v1/trips/'.$unknownTripId)->assertNotFound();

    TripServer::actingAs($outsider)
        ->tool(ShowTripTool::class, ['trip_id' => $trip->getKey()])
        ->assertHasErrors(['Trip not found.']);
    TripServer::actingAs($outsider)
        ->tool(ShowTripTool::class, ['trip_id' => $unknownTripId])
        ->assertHasErrors(['Trip not found.']);

    TripServer::actingAs($outsider)
        ->tool(ShowPackingListsTool::class, ['trip_id' => $trip->getKey()])
        ->assertHasErrors(['Trip not found.']);
    TripServer::actingAs($outsider)
        ->tool(ShowPackingListsTool::class, ['trip_id' => $unknownTripId])
        ->assertHasErrors(['Trip not found.']);
    TripServer::actingAs($outsider)
        ->tool(ListTripsTool::class)
        ->assertOk()
        ->assertDontSee('Private Existing Destination');
});

it('revokes the removed members next-request access across interfaces and preserves trip lists', function () {
    $admin = User::factory()->create();
    $member = User::factory()->create();
    $trip = Trip::factory()->create(['destination' => 'Shared Trip']);
    $trip->members()->attach($admin, ['role' => 'admin']);
    $trip->members()->attach($member, ['role' => 'member']);
    $participant = TripParticipant::factory()->for($trip)->create([
        'name' => 'Accountless Participant',
        'user_id' => null,
    ]);
    $sharedItem = PackingItem::factory()->for($trip)->create(['name' => 'Shared phone']);
    $participantItem = PackingItem::factory()->for($trip)->for($participant, 'participant')->create([
        'name' => 'Participant passport',
    ]);

    $this->actingAs($admin)
        ->delete(route('trips.members.destroy', [$trip, $member]))
        ->assertRedirect(route('trips.show', $trip));

    $this->actingAs($member)
        ->get(route('trips.show', $trip))
        ->assertNotFound();

    Passport::actingAs($member);
    $this->getJson('/api/v1/trips/'.$trip->getKey())->assertNotFound();
    $this->getJson('/api/v1/trips/'.$trip->getKey().'/participants')->assertNotFound();
    $this->getJson('/api/v1/trips/'.$trip->getKey().'/packing-items')->assertNotFound();

    TripServer::actingAs($member)
        ->tool(ListTripsTool::class)
        ->assertOk()
        ->assertDontSee('Shared Trip');
    TripServer::actingAs($member)
        ->tool(ShowTripTool::class, ['trip_id' => $trip->getKey()])
        ->assertHasErrors(['Trip not found.']);
    TripServer::actingAs($member)
        ->tool(ShowPackingListsTool::class, ['trip_id' => $trip->getKey()])
        ->assertHasErrors(['Trip not found.']);

    $this->assertModelExists($participant);
    $this->assertModelExists($sharedItem);
    $this->assertModelExists($participantItem);
});
