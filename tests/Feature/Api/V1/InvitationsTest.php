<?php

use App\Models\Trip;
use App\Models\TripInvitation;
use App\Models\User;
use App\Notifications\TripInvitationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Passport\Passport;

uses(RefreshDatabase::class);

test('invitation endpoints require a passport token', function (): void {
    $this->postJson('/api/v1/trips/1/invitations', [])->assertUnauthorized();
    $this->postJson('/api/v1/invitations/accept', [])->assertUnauthorized();
});

test('a trip admin can create and delete an invitation without exposing its token', function (): void {
    Notification::fake();
    $admin = User::factory()->create();
    $trip = Trip::factory()->create();
    $trip->members()->attach($admin, ['role' => 'admin']);
    Passport::actingAs($admin);

    $response = $this->postJson('/api/v1/trips/'.$trip->id.'/invitations', [
        'email' => '  Guest@Example.com ',
        'role' => 'member',
    ])
        ->assertCreated()
        ->assertJsonPath('data.email', 'guest@example.com')
        ->assertJsonMissingPath('data.token')
        ->assertJsonMissingPath('data.token_hash');

    $invitationId = $response->json('data.id');
    $invitation = TripInvitation::query()->findOrFail($invitationId);

    expect($invitation->token_hash)->toHaveLength(64)
        ->and($invitation->email)->toBe('guest@example.com');

    Notification::assertSentOnDemand(TripInvitationNotification::class);

    $this->deleteJson('/api/v1/trips/'.$trip->id.'/invitations/'.$invitationId)
        ->assertNoContent();

    $this->assertDatabaseMissing('trip_invitations', ['id' => $invitationId]);
});

test('trip members cannot create or delete invitations', function (): void {
    $member = User::factory()->create();
    $trip = Trip::factory()->create();
    $trip->members()->attach($member, ['role' => 'member']);
    $invitation = TripInvitation::factory()->create(['trip_id' => $trip->id]);
    Passport::actingAs($member);

    $this->postJson('/api/v1/trips/'.$trip->id.'/invitations', [
        'email' => 'guest@example.com',
        'role' => 'member',
    ])->assertForbidden();

    $this->deleteJson('/api/v1/trips/'.$trip->id.'/invitations/'.$invitation->id)
        ->assertForbidden();

    $this->assertDatabaseHas('trip_invitations', ['id' => $invitation->id]);
});

test('invitation creation validates email and role', function (): void {
    $admin = User::factory()->create();
    $trip = Trip::factory()->create();
    $trip->members()->attach($admin, ['role' => 'admin']);
    Passport::actingAs($admin);

    $this->postJson('/api/v1/trips/'.$trip->id.'/invitations', [
        'email' => 'not-an-email',
        'role' => 'owner',
    ])->assertUnprocessable()->assertJsonValidationErrors(['email', 'role']);
});

test('accepting an invitation requires a verified matching email and accepts only once', function (): void {
    $guest = User::factory()->create(['email' => 'guest@example.com']);
    $trip = Trip::factory()->create();
    $rawToken = str_repeat('a', 64);
    $invitation = TripInvitation::factory()->create([
        'trip_id' => $trip->id,
        'email' => $guest->email,
        'role' => 'member',
        'token_hash' => hash('sha256', $rawToken),
        'expires_at' => now()->addDay(),
    ]);
    Passport::actingAs($guest);

    $this->postJson('/api/v1/invitations/accept', ['token' => $rawToken])
        ->assertOk()
        ->assertJsonPath('data.id', $trip->id);

    $this->assertDatabaseHas('trip_user', [
        'trip_id' => $trip->id,
        'user_id' => $guest->id,
        'role' => 'member',
    ]);
    expect($invitation->fresh()->accepted_at)->not->toBeNull();

    $this->postJson('/api/v1/invitations/accept', ['token' => $rawToken])->assertNotFound();
});

test('invitation acceptance returns validation and not found errors for invalid or expired tokens', function (): void {
    $guest = User::factory()->create();
    $trip = Trip::factory()->create();
    $rawToken = str_repeat('b', 64);
    TripInvitation::factory()->create([
        'trip_id' => $trip->id,
        'email' => $guest->email,
        'token_hash' => hash('sha256', $rawToken),
        'expires_at' => now()->subSecond(),
    ]);
    Passport::actingAs($guest);

    $this->postJson('/api/v1/invitations/accept', ['token' => 'short'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('token');

    $this->postJson('/api/v1/invitations/accept', ['token' => $rawToken])->assertNotFound();
});

test('an invitation cannot be accepted by a different verified email', function (): void {
    $guest = User::factory()->create(['email' => 'someone-else@example.com']);
    $trip = Trip::factory()->create();
    $rawToken = str_repeat('c', 64);
    TripInvitation::factory()->create([
        'trip_id' => $trip->id,
        'email' => 'guest@example.com',
        'token_hash' => hash('sha256', $rawToken),
        'expires_at' => now()->addDay(),
    ]);
    Passport::actingAs($guest);

    $this->postJson('/api/v1/invitations/accept', ['token' => $rawToken])->assertForbidden();
});

test('invitation ids are scoped to the selected trip', function (): void {
    $admin = User::factory()->create();
    $trip = Trip::factory()->create();
    $otherTrip = Trip::factory()->create();
    $trip->members()->attach($admin, ['role' => 'admin']);
    $invitation = TripInvitation::factory()->create(['trip_id' => $otherTrip->id]);
    Passport::actingAs($admin);

    $this->deleteJson('/api/v1/trips/'.$trip->id.'/invitations/'.$invitation->id)
        ->assertNotFound();
});
