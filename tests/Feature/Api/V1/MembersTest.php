<?php

use App\Models\Trip;
use App\Models\TripParticipant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;

uses(RefreshDatabase::class);

test('member role routes require authentication', function (): void {
    $this->patchJson('/api/v1/trips/1/members/2', ['role' => 'admin'])->assertUnauthorized();
});

test('a trip admin can promote a member and the new role is returned', function (): void {
    $admin = User::factory()->create();
    $member = User::factory()->create();
    $trip = Trip::factory()->create();
    $trip->members()->attach($admin, ['role' => 'admin']);
    $trip->members()->attach($member, ['role' => 'member']);
    Passport::actingAs($admin);

    $this->patchJson('/api/v1/trips/'.$trip->id.'/members/'.$member->id, ['role' => 'admin'])
        ->assertOk()
        ->assertJsonPath('data.id', $member->id)
        ->assertJsonPath('data.role', 'admin');

    $this->assertDatabaseHas('trip_user', [
        'trip_id' => $trip->id,
        'user_id' => $member->id,
        'role' => 'admin',
    ]);
});

test('an admin may demote an admin when another admin remains', function (): void {
    $admin = User::factory()->create();
    $otherAdmin = User::factory()->create();
    $trip = Trip::factory()->create();
    $trip->members()->attach($admin, ['role' => 'admin']);
    $trip->members()->attach($otherAdmin, ['role' => 'admin']);
    Passport::actingAs($admin);

    $this->patchJson('/api/v1/trips/'.$trip->id.'/members/'.$otherAdmin->id, ['role' => 'member'])
        ->assertOk()
        ->assertJsonPath('data.role', 'member');
});

test('a trip admin cannot demote or remove the last admin', function (): void {
    $admin = User::factory()->create();
    $trip = Trip::factory()->create();
    $trip->members()->attach($admin, ['role' => 'admin']);
    Passport::actingAs($admin);

    $this->patchJson('/api/v1/trips/'.$trip->id.'/members/'.$admin->id, ['role' => 'member'])
        ->assertForbidden();
    $this->deleteJson('/api/v1/trips/'.$trip->id.'/members/'.$admin->id)->assertForbidden();

    $this->assertDatabaseHas('trip_user', [
        'trip_id' => $trip->id,
        'user_id' => $admin->id,
        'role' => 'admin',
    ]);
});

test('a trip member cannot change or remove other member roles', function (): void {
    $member = User::factory()->create();
    $otherMember = User::factory()->create();
    $trip = Trip::factory()->create();
    $trip->members()->attach($member, ['role' => 'member']);
    $trip->members()->attach($otherMember, ['role' => 'member']);
    Passport::actingAs($member);

    $this->patchJson('/api/v1/trips/'.$trip->id.'/members/'.$otherMember->id, ['role' => 'admin'])
        ->assertForbidden();
    $this->deleteJson('/api/v1/trips/'.$trip->id.'/members/'.$otherMember->id)
        ->assertForbidden();
});

test('member role updates reject invalid roles', function (): void {
    $admin = User::factory()->create();
    $member = User::factory()->create();
    $trip = Trip::factory()->create();
    $trip->members()->attach($admin, ['role' => 'admin']);
    $trip->members()->attach($member, ['role' => 'member']);
    Passport::actingAs($admin);

    $this->patchJson('/api/v1/trips/'.$trip->id.'/members/'.$member->id, ['role' => 'owner'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('role');
});

test('member routes return not found for unknown or outside-trip members', function (): void {
    $admin = User::factory()->create();
    $outsideMember = User::factory()->create();
    $trip = Trip::factory()->create();
    $otherTrip = Trip::factory()->create();
    $trip->members()->attach($admin, ['role' => 'admin']);
    $otherTrip->members()->attach($outsideMember, ['role' => 'member']);
    Passport::actingAs($admin);

    $this->patchJson('/api/v1/trips/'.$trip->id.'/members/999999', ['role' => 'admin'])->assertNotFound();
    $this->deleteJson('/api/v1/trips/'.$trip->id.'/members/'.$outsideMember->id)->assertNotFound();
});

test('removing a member preserves trip participants and revokes next-request access', function (): void {
    $admin = User::factory()->create();
    $member = User::factory()->create();
    $trip = Trip::factory()->create();
    $trip->members()->attach($admin, ['role' => 'admin']);
    $trip->members()->attach($member, ['role' => 'member']);
    $participant = TripParticipant::factory()->create(['trip_id' => $trip->id]);
    Passport::actingAs($admin);

    $this->deleteJson('/api/v1/trips/'.$trip->id.'/members/'.$member->id)->assertNoContent();
    $this->assertDatabaseHas('trip_participants', ['id' => $participant->id, 'trip_id' => $trip->id]);
    $this->assertDatabaseMissing('trip_user', ['trip_id' => $trip->id, 'user_id' => $member->id]);

    Passport::actingAs($member);
    $this->getJson('/api/v1/trips/'.$trip->id.'/participants')->assertNotFound();
});
