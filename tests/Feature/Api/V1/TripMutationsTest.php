<?php

use App\Models\PackingItem;
use App\Models\Trip;
use App\Models\TripInspirationItem;
use App\Models\TripInspirationProjection;
use App\Models\TripInvitation;
use App\Models\TripParticipant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;

uses(RefreshDatabase::class);

test('trip mutation routes require passport authentication', function (): void {
    $this->patchJson('/api/v1/trips/1', ['destination' => 'Oslo'])->assertUnauthorized();
    $this->deleteJson('/api/v1/trips/1')->assertUnauthorized();
});

test('a trip member can partially update a trip and preserve omitted fields', function (): void {
    $member = User::factory()->create();
    $trip = Trip::factory()->create([
        'destination' => 'Oslo',
        'start_date' => '2026-11-02',
        'end_date' => '2026-11-04',
        'type' => 'family',
        'transport' => 'train',
        'accommodation' => 'cabin',
        'activities' => ['hiking'],
        'description' => 'Original notes',
    ]);
    $trip->members()->attach($member, ['role' => 'member']);
    Passport::actingAs($member);

    $this->patchJson('/api/v1/trips/'.$trip->id, ['destination' => 'Bergen'])
        ->assertOk()
        ->assertJsonPath('data.destination', 'Bergen')
        ->assertJsonPath('data.start_date', '2026-11-02')
        ->assertJsonPath('data.end_date', '2026-11-04')
        ->assertJsonPath('data.activities', ['hiking'])
        ->assertJsonPath('data.description', 'Original notes');
});

test('trip updates validate catalog values and the merged date range', function (): void {
    $member = User::factory()->create();
    $trip = Trip::factory()->create([
        'start_date' => '2026-11-02',
        'end_date' => '2026-11-04',
    ]);
    $trip->members()->attach($member, ['role' => 'member']);
    Passport::actingAs($member);

    $this->patchJson('/api/v1/trips/'.$trip->id, [
        'type' => 'private',
        'activities' => ['secret event'],
        'start_date' => '2026-11-05',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['type', 'activities.0', 'end_date']);

    $this->patchJson('/api/v1/trips/'.$trip->id, ['end_date' => 'not-a-date'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('end_date');
});

test('a trip member cannot delete a trip', function (): void {
    $member = User::factory()->create();
    $trip = Trip::factory()->create();
    $trip->members()->attach($member, ['role' => 'member']);
    Passport::actingAs($member);

    $this->deleteJson('/api/v1/trips/'.$trip->id)->assertForbidden();

    $this->assertModelExists($trip);
});

test('a trip admin can delete a trip and its dependent records', function (): void {
    $admin = User::factory()->create();
    $trip = Trip::factory()->create();
    $trip->members()->attach($admin, ['role' => 'admin']);
    $participant = TripParticipant::factory()->create(['trip_id' => $trip->id]);
    $packingItem = PackingItem::factory()->create([
        'trip_id' => $trip->id,
        'participant_id' => $participant->id,
    ]);
    $invitation = TripInvitation::factory()->create(['trip_id' => $trip->id]);
    $projection = TripInspirationProjection::factory()->create([
        'trip_id' => $trip->id,
        'owner_id' => $trip->created_by_user_id,
    ]);
    $projectionItem = TripInspirationItem::factory()->create(['projection_id' => $projection->id]);
    Passport::actingAs($admin);

    $this->deleteJson('/api/v1/trips/'.$trip->id)->assertNoContent();

    $this->assertModelMissing($trip);
    $this->assertModelMissing($participant);
    $this->assertModelMissing($packingItem);
    $this->assertModelMissing($invitation);
    $this->assertModelMissing($projection);
    $this->assertModelMissing($projectionItem);
    $this->assertDatabaseMissing('trip_user', ['trip_id' => $trip->id]);
});

test('trip update and delete return not found for a trip outside membership', function (): void {
    $user = User::factory()->create();
    $trip = Trip::factory()->create();
    Passport::actingAs($user);

    $this->patchJson('/api/v1/trips/'.$trip->id, ['destination' => 'Not visible'])->assertNotFound();
    $this->deleteJson('/api/v1/trips/'.$trip->id)->assertNotFound();
    $this->assertModelExists($trip);
});

test('updating trip dates refreshes or removes the inspiration projection', function (): void {
    $member = User::factory()->create();
    $trip = Trip::factory()->create([
        'start_date' => '2026-10-01',
        'end_date' => '2026-10-03',
        'type' => 'adventure',
        'transport' => 'plane',
        'accommodation' => 'hotel',
    ]);
    $trip->members()->attach($member, ['role' => 'member']);
    $projection = TripInspirationProjection::factory()->create([
        'trip_id' => $trip->id,
        'owner_id' => $trip->created_by_user_id,
    ]);
    $projectionItem = TripInspirationItem::factory()->create(['projection_id' => $projection->id]);
    Passport::actingAs($member);

    $this->patchJson('/api/v1/trips/'.$trip->id, ['end_date' => '2026-10-30'])
        ->assertOk()
        ->assertJsonPath('data.end_date', '2026-10-30');

    $this->assertModelMissing($projection);
    $this->assertModelMissing($projectionItem);
});
