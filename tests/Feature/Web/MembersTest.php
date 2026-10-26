<?php

use App\Models\PackingItem;
use App\Models\Trip;
use App\Models\TripParticipant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lets an admin promote a trip member', function () {
    $admin = User::factory()->create();
    $member = User::factory()->create();
    $trip = Trip::factory()->create();
    $trip->members()->attach($admin, ['role' => 'admin']);
    $trip->members()->attach($member, ['role' => 'member']);

    $this->actingAs($admin)
        ->patch(route('trips.members.update', [$trip, $member]), ['role' => 'admin'])
        ->assertRedirect(route('trips.show', $trip));

    $this->assertDatabaseHas('trip_user', [
        'trip_id' => $trip->getKey(),
        'user_id' => $member->getKey(),
        'role' => 'admin',
    ]);
});

it('prevents demoting or removing the only admin', function () {
    $admin = User::factory()->create();
    $trip = Trip::factory()->create();
    $trip->members()->attach($admin, ['role' => 'admin']);

    $this->actingAs($admin)
        ->patch(route('trips.members.update', [$trip, $admin]), ['role' => 'member'])
        ->assertForbidden();
    $this->delete(route('trips.members.destroy', [$trip, $admin]))
        ->assertForbidden();

    $this->assertDatabaseHas('trip_user', [
        'trip_id' => $trip->getKey(),
        'user_id' => $admin->getKey(),
        'role' => 'admin',
    ]);
});

it('allows changing and removing an admin when multiple admins exist', function () {
    $admin = User::factory()->create();
    $otherAdmin = User::factory()->create();
    $trip = Trip::factory()->create();
    $trip->members()->attach($admin, ['role' => 'admin']);
    $trip->members()->attach($otherAdmin, ['role' => 'admin']);

    $this->actingAs($admin)
        ->patch(route('trips.members.update', [$trip, $otherAdmin]), ['role' => 'member'])
        ->assertRedirect(route('trips.show', $trip));
    $this->assertDatabaseHas('trip_user', [
        'trip_id' => $trip->getKey(),
        'user_id' => $otherAdmin->getKey(),
        'role' => 'member',
    ]);

    $this->delete(route('trips.members.destroy', [$trip, $otherAdmin]))
        ->assertRedirect(route('trips.show', $trip));
    $this->assertDatabaseMissing('trip_user', [
        'trip_id' => $trip->getKey(),
        'user_id' => $otherAdmin->getKey(),
    ]);
});

it('denies member controls and actions to regular trip members', function () {
    $admin = User::factory()->create();
    $member = User::factory()->create();
    $trip = Trip::factory()->create();
    $trip->members()->attach($admin, ['role' => 'admin']);
    $trip->members()->attach($member, ['role' => 'member']);

    $this->actingAs($member)
        ->get(route('trips.show', $trip))
        ->assertOk()
        ->assertDontSee('Save role')
        ->assertDontSee('Remove member');

    $this->patch(route('trips.members.update', [$trip, $admin]), ['role' => 'member'])
        ->assertForbidden();
    $this->delete(route('trips.members.destroy', [$trip, $admin]))
        ->assertForbidden();
});

it('returns not found when a target is not a member of the current trip', function () {
    $admin = User::factory()->create();
    $trip = Trip::factory()->create();
    $otherTrip = Trip::factory()->create();
    $trip->members()->attach($admin, ['role' => 'admin']);
    $otherMember = User::factory()->create();
    $otherTrip->members()->attach($otherMember, ['role' => 'member']);

    $this->actingAs($admin)
        ->patch(route('trips.members.update', [$trip, $otherMember]), ['role' => 'admin'])
        ->assertNotFound();
});

it('keeps participants and packing lists after removing their linked member', function () {
    $admin = User::factory()->create();
    $removedMember = User::factory()->create();
    $trip = Trip::factory()->create();
    $trip->members()->attach($admin, ['role' => 'admin']);
    $trip->members()->attach($removedMember, ['role' => 'member']);
    $participant = TripParticipant::factory()->for($trip)->create([
        'user_id' => $removedMember->getKey(),
        'name' => 'Casey',
    ]);
    $item = PackingItem::factory()->for($trip)->create([
        'participant_id' => $participant->getKey(),
        'name' => 'Casey’s jacket',
    ]);

    $this->actingAs($admin)
        ->delete(route('trips.members.destroy', [$trip, $removedMember]))
        ->assertRedirect(route('trips.show', $trip));

    $this->assertDatabaseMissing('trip_user', [
        'trip_id' => $trip->getKey(),
        'user_id' => $removedMember->getKey(),
    ]);
    $this->assertDatabaseHas('trip_participants', ['id' => $participant->getKey()]);
    $this->assertDatabaseHas('packing_items', ['id' => $item->getKey()]);

    $this->actingAs($removedMember)
        ->get(route('trips.show', $trip))
        ->assertNotFound();
});
