<?php

namespace App\Actions;

use App\Models\Trip;
use App\Models\TripInvitation;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AcceptTripInvitation
{
    public function __invoke(User $user, string $rawToken): Trip
    {
        Validator::make(['token' => $rawToken], [
            'token' => ['required', 'string', 'size:64'],
        ])->validate();

        if (! $user->hasVerifiedEmail()) {
            throw new AuthorizationException;
        }

        return DB::transaction(function () use ($user, $rawToken): Trip {
            $invitation = TripInvitation::query()
                ->where('token_hash', hash('sha256', $rawToken))
                ->whereNull('accepted_at')
                ->where('expires_at', '>', now())
                ->lockForUpdate()
                ->firstOrFail();

            if (! hash_equals($invitation->email, Str::lower(trim($user->email)))) {
                throw new AuthorizationException;
            }

            $trip = $invitation->trip;

            if (! $trip->members()->whereKey($user->getKey())->exists()) {
                $trip->members()->attach($user, ['role' => $invitation->role]);
            }

            $invitation->update(['accepted_at' => now()]);

            return $trip;
        });
    }
}
