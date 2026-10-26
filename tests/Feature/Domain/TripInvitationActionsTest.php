<?php

use App\Actions\AcceptTripInvitation;
use App\Actions\CreateTripInvitation;
use App\Models\Trip;
use App\Models\TripInvitation;
use App\Models\User;
use App\Notifications\TripInvitationNotification;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

it('creates a normalized invitation and emails its one-time token', function () {
    $admin = User::factory()->create();
    $trip = Trip::factory()->create();
    $trip->members()->attach($admin, ['role' => 'admin']);
    $admin->setCurrentTenant($trip);

    Route::get('/trip-invitations/{token}', fn (string $token) => response()->noContent())
        ->name('trip-invitations.show');
    app('router')->getRoutes()->refreshNameLookups();
    Notification::fake();

    $invitation = (new CreateTripInvitation)($admin, $trip, '  INVITEE@EXAMPLE.COM ', 'admin');

    expect($invitation->email)->toBe('invitee@example.com')
        ->and($invitation->role)->toBe('admin')
        ->and($invitation->token_hash)->toMatch('/^[a-f0-9]{64}$/')
        ->and($invitation->toArray())->not->toHaveKey('token_hash');

    $this->assertDatabaseHas('trip_invitations', [
        'id' => $invitation->getKey(),
        'email' => 'invitee@example.com',
        'role' => 'admin',
    ]);

    Notification::assertSentOnDemand(
        TripInvitationNotification::class,
        function (TripInvitationNotification $notification, array $channels, object $notifiable) use ($invitation): bool {
            $mail = $notification->toMail($notifiable);
            $rawToken = basename((string) parse_url($mail->actionUrl, PHP_URL_PATH));

            return $channels === ['mail']
                && $notifiable->routes['mail'] === 'invitee@example.com'
                && $mail->actionText === __('trip-invitations.action')
                && hash('sha256', $rawToken) === $invitation->token_hash;
        },
    );
});

it('accepts an invitation once for the verified invited user', function () {
    $user = User::factory()->create(['email' => 'invitee@example.com']);
    $trip = Trip::factory()->create();
    $rawToken = str_repeat('a', 64);
    $invitation = TripInvitation::factory()->for($trip)->create([
        'email' => 'invitee@example.com',
        'role' => 'member',
        'token_hash' => hash('sha256', $rawToken),
        'expires_at' => now()->addDay(),
    ]);

    $acceptedTrip = (new AcceptTripInvitation)($user, $rawToken);

    expect($acceptedTrip->is($trip))->toBeTrue()
        ->and($trip->members()->whereKey($user->getKey())->first()?->pivot?->role)->toBe('member')
        ->and($invitation->fresh()->accepted_at)->not->toBeNull();

    expect(fn () => (new AcceptTripInvitation)($user, $rawToken))
        ->toThrow(ModelNotFoundException::class);
});

it('rejects acceptance by a user with an unverified email', function () {
    $user = User::factory()->unverified()->create(['email' => 'invitee@example.com']);
    $trip = Trip::factory()->create();
    $rawToken = str_repeat('b', 64);
    $invitation = TripInvitation::factory()->for($trip)->create([
        'email' => 'invitee@example.com',
        'token_hash' => hash('sha256', $rawToken),
    ]);

    expect(fn () => (new AcceptTripInvitation)($user, $rawToken))
        ->toThrow(AuthorizationException::class);

    expect($invitation->fresh()->accepted_at)->toBeNull()
        ->and($trip->members()->whereKey($user->getKey())->exists())->toBeFalse();
});

it('rejects acceptance when the verified email does not match the invitation', function () {
    $user = User::factory()->create(['email' => 'other@example.com']);
    $trip = Trip::factory()->create();
    $rawToken = str_repeat('c', 64);
    $invitation = TripInvitation::factory()->for($trip)->create([
        'email' => 'invitee@example.com',
        'token_hash' => hash('sha256', $rawToken),
    ]);

    expect(fn () => (new AcceptTripInvitation)($user, $rawToken))
        ->toThrow(AuthorizationException::class);

    expect($invitation->fresh()->accepted_at)->toBeNull()
        ->and($trip->members()->whereKey($user->getKey())->exists())->toBeFalse();
});

it('does not accept an expired invitation', function () {
    $user = User::factory()->create(['email' => 'invitee@example.com']);
    $trip = Trip::factory()->create();
    $rawToken = str_repeat('d', 64);
    TripInvitation::factory()->for($trip)->create([
        'email' => 'invitee@example.com',
        'token_hash' => hash('sha256', $rawToken),
        'expires_at' => now()->subSecond(),
    ]);

    expect(fn () => (new AcceptTripInvitation)($user, $rawToken))
        ->toThrow(ModelNotFoundException::class);

    expect($trip->members()->whereKey($user->getKey())->exists())->toBeFalse();
});

it('does not allow a trip member to create invitations', function () {
    $member = User::factory()->create();
    $trip = Trip::factory()->create();
    $trip->members()->attach($member, ['role' => 'member']);
    $member->setCurrentTenant($trip);
    Notification::fake();

    expect(fn () => (new CreateTripInvitation)($member, $trip, 'invitee@example.com', 'member'))
        ->toThrow(AuthorizationException::class);

    expect($trip->invitations()->count())->toBe(0);
    Notification::assertNothingSent();
});

it('rejects invitation roles outside admin and member', function () {
    $admin = User::factory()->create();
    $trip = Trip::factory()->create();
    $trip->members()->attach($admin, ['role' => 'admin']);
    $admin->setCurrentTenant($trip);
    Notification::fake();

    expect(fn () => (new CreateTripInvitation)($admin, $trip, 'invitee@example.com', 'owner'))
        ->toThrow(ValidationException::class);

    expect($trip->invitations()->count())->toBe(0);
    Notification::assertNothingSent();
});
