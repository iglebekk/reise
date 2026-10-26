<?php

namespace App\Actions;

use App\Models\Trip;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class RemoveTripMember
{
    public function __invoke(User $actor, Trip $trip, User $member): void
    {
        DB::transaction(function () use ($actor, $trip, $member): void {
            $lockedTrip = Trip::query()
                ->whereKey($trip->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            DB::table('trip_user')
                ->where('trip_id', $lockedTrip->getKey())
                ->orderBy('user_id')
                ->lockForUpdate()
                ->get();

            Gate::forUser($actor)->authorize('removeMember', [$lockedTrip, $member]);

            $lockedTrip->members()->detach($member->getKey());
        }, attempts: 3);
    }
}
