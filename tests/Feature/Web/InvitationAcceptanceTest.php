<?php

use App\Actions\CreateTripInvitation;
use App\Models\Trip;
use App\Models\TripInvitation;
use App\Models\User;
use App\Notifications\TripInvitationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

it('renders the emailed invitation link without automatically accepting it', function () {
    Notification::fake();
    $admin = User::factory()->create();
    $invitee = User::factory()->create(['email' => 'invitee@example.com']);
    $trip = Trip::factory()->create(['destination' => 'Oslo']);
    $trip->members()->attach($admin, ['role' => 'admin']);
    $admin->setCurrentTenant($trip);

    $invitation = (new CreateTripInvitation)($admin, $trip, $invitee->email, 'member');
    $actionUrl = null;

    Notification::assertSentOnDemand(
        TripInvitationNotification::class,
        function (TripInvitationNotification $notification, array $channels, object $notifiable) use (&$actionUrl): bool {
            $actionUrl = $notification->toMail($notifiable)->actionUrl;
            $rawToken = basename((string) parse_url($actionUrl, PHP_URL_PATH));

            return $channels === ['mail']
                && parse_url($actionUrl, PHP_URL_PATH) === route('trip-invitations.show', ['token' => $rawToken], false);
        },
    );

    $rawToken = basename((string) parse_url((string) $actionUrl, PHP_URL_PATH));
    expect(hash('sha256', $rawToken))->toBe($invitation->token_hash);

    $this->actingAs($invitee)
        ->get((string) $actionUrl)
        ->assertOk()
        ->assertSee('Oslo')
        ->assertSee('Accept invitation');

    expect($invitation->fresh()->accepted_at)->toBeNull()
        ->and($trip->members()->whereKey($invitee->getKey())->exists())->toBeFalse();
});

it('accepts once and lets the invited member open the trip', function () {
    $invitee = User::factory()->create(['email' => 'invitee@example.com']);
    $trip = Trip::factory()->create(['destination' => 'Tromsø']);
    $rawToken = str_repeat('a', 64);
    $invitation = TripInvitation::factory()->for($trip)->create([
        'email' => $invitee->email,
        'role' => 'member',
        'token_hash' => hash('sha256', $rawToken),
        'expires_at' => now()->addDay(),
    ]);

    $this->actingAs($invitee)
        ->post(route('trip-invitations.accept'), ['token' => $rawToken])
        ->assertRedirect(route('trips.show', $trip));

    $this->assertDatabaseHas('trip_user', [
        'trip_id' => $trip->getKey(),
        'user_id' => $invitee->getKey(),
        'role' => 'member',
    ]);
    expect($invitation->fresh()->accepted_at)->not->toBeNull();

    $this->get(route('trips.show', $trip))->assertOk()->assertSee('Tromsø');
    $this->post(route('trip-invitations.accept'), ['token' => $rawToken])->assertNotFound();
});

it('blocks guests and unverified users from reviewing invitations', function () {
    $rawToken = str_repeat('b', 64);
    $trip = Trip::factory()->create();
    TripInvitation::factory()->for($trip)->create([
        'email' => 'invitee@example.com',
        'token_hash' => hash('sha256', $rawToken),
        'expires_at' => now()->addDay(),
    ]);

    $this->get(route('trip-invitations.show', ['token' => $rawToken]))
        ->assertRedirect(route('login'));

    $unverifiedUser = User::factory()->unverified()->create(['email' => 'invitee@example.com']);
    $this->actingAs($unverifiedUser)
        ->get(route('trip-invitations.show', ['token' => $rawToken]))
        ->assertRedirect(route('verification.notice'));
});

it('hides invitations from other emails and for expired tokens', function () {
    $otherUser = User::factory()->create(['email' => 'someone-else@example.com']);
    $trip = Trip::factory()->create();
    $validToken = str_repeat('c', 64);
    $expiredToken = str_repeat('d', 64);
    TripInvitation::factory()->for($trip)->create([
        'email' => 'invitee@example.com',
        'token_hash' => hash('sha256', $validToken),
        'expires_at' => now()->addDay(),
    ]);
    TripInvitation::factory()->for($trip)->create([
        'email' => $otherUser->email,
        'token_hash' => hash('sha256', $expiredToken),
        'expires_at' => now()->subSecond(),
    ]);

    $this->actingAs($otherUser)
        ->get(route('trip-invitations.show', ['token' => $validToken]))
        ->assertNotFound();
    $this->get(route('trip-invitations.show', ['token' => $expiredToken]))
        ->assertNotFound();
});

it('validates missing or malformed acceptance tokens', function () {
    $invitee = User::factory()->create();

    $this->actingAs($invitee)
        ->postJson(route('trip-invitations.accept'), ['token' => 'short'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('token');
});
