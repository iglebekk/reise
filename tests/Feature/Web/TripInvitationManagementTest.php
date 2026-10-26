<?php

use App\Models\Trip;
use App\Models\TripInvitation;
use App\Models\User;
use App\Notifications\TripInvitationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

it('lets a trip admin create and list invitations without exposing their token', function () {
    Notification::fake();
    $admin = User::factory()->create();
    $trip = Trip::factory()->create();
    $trip->members()->attach($admin, ['role' => 'admin']);

    $response = $this->actingAs($admin)->post(route('trips.invitations.store', $trip), [
        'email' => '  Guest@Example.com ',
        'role' => 'member',
    ]);

    $invitation = TripInvitation::query()->where('trip_id', $trip->getKey())->firstOrFail();
    $response->assertRedirect(route('trips.show', $trip));
    $this->assertDatabaseHas('trip_invitations', [
        'id' => $invitation->getKey(),
        'email' => 'guest@example.com',
        'role' => 'member',
    ]);
    Notification::assertSentOnDemand(TripInvitationNotification::class);

    $this->get(route('trips.show', $trip))
        ->assertOk()
        ->assertSee('guest@example.com')
        ->assertSee('Member')
        ->assertSee('Pending')
        ->assertDontSee($invitation->token_hash);
});

it('lets a trip admin revoke only an invitation from the current trip', function () {
    $admin = User::factory()->create();
    $trip = Trip::factory()->create();
    $otherTrip = Trip::factory()->create();
    $trip->members()->attach($admin, ['role' => 'admin']);
    $invitation = TripInvitation::factory()->for($trip)->create();
    $otherInvitation = TripInvitation::factory()->for($otherTrip)->create();

    $this->actingAs($admin)
        ->delete(route('trips.invitations.destroy', [$trip, $invitation]))
        ->assertRedirect(route('trips.show', $trip));

    $this->assertDatabaseMissing('trip_invitations', ['id' => $invitation->getKey()]);
    $this->assertDatabaseHas('trip_invitations', ['id' => $otherInvitation->getKey()]);

    $this->delete(route('trips.invitations.destroy', [$trip, $otherInvitation]))
        ->assertNotFound();
});

it('validates invitation email and role', function () {
    $admin = User::factory()->create();
    $trip = Trip::factory()->create();
    $trip->members()->attach($admin, ['role' => 'admin']);

    $this->actingAs($admin)
        ->postJson(route('trips.invitations.store', $trip), [
            'email' => 'not-an-email',
            'role' => 'owner',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email', 'role']);

    $this->assertDatabaseCount('trip_invitations', 0);
});

it('hides invitation management from regular trip members', function () {
    $member = User::factory()->create();
    $trip = Trip::factory()->create();
    $trip->members()->attach($member, ['role' => 'member']);
    $invitation = TripInvitation::factory()->for($trip)->create();

    $this->actingAs($member)
        ->get(route('trips.show', $trip))
        ->assertOk()
        ->assertDontSee('Invite a trip member')
        ->assertDontSee($invitation->email);

    $this->post(route('trips.invitations.store', $trip), [
        'email' => 'guest@example.com',
        'role' => 'member',
    ])->assertForbidden();

    $this->delete(route('trips.invitations.destroy', [$trip, $invitation]))
        ->assertForbidden();
});

it('requires authentication and verified email for invitation management', function () {
    $trip = Trip::factory()->create();
    $this->post(route('trips.invitations.store', $trip), [
        'email' => 'guest@example.com',
        'role' => 'member',
    ])->assertRedirect(route('login'));

    $unverifiedAdmin = User::factory()->unverified()->create();
    $trip->members()->attach($unverifiedAdmin, ['role' => 'admin']);

    $this->actingAs($unverifiedAdmin)
        ->post(route('trips.invitations.store', $trip), [
            'email' => 'guest@example.com',
            'role' => 'member',
        ])->assertRedirect(route('verification.notice'));
});
