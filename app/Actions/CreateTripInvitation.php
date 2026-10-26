<?php

namespace App\Actions;

use App\Models\Trip;
use App\Models\TripInvitation;
use App\Models\User;
use App\Notifications\TripInvitationNotification;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CreateTripInvitation
{
    public function __invoke(User $actor, Trip $trip, string $email, string $role): TripInvitation
    {
        Gate::forUser($actor)->authorize('invite', $trip);

        $email = Str::lower(trim($email));

        Validator::make([
            'email' => $email,
            'role' => $role,
        ], [
            'email' => ['required', 'email', 'max:255'],
            'role' => ['required', Rule::in(['admin', 'member'])],
        ])->validate();

        $rawToken = Str::random(64);
        $expiresAt = now()->addDays(7);

        $invitation = $trip->invitations()->create([
            'email' => $email,
            'role' => $role,
            'token_hash' => hash('sha256', $rawToken),
            'expires_at' => $expiresAt,
        ]);

        Notification::route('mail', $email)->notify(
            new TripInvitationNotification($trip->destination, $rawToken, $expiresAt)
        );

        return $invitation;
    }
}
