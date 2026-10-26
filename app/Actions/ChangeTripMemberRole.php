<?php

namespace App\Actions;

use App\Models\Trip;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ChangeTripMemberRole
{
    public function __invoke(User $actor, Trip $trip, User $member, string $role): void
    {
        DB::transaction(function () use ($actor, $trip, $member, $role): void {
            $lockedTrip = Trip::query()
                ->whereKey($trip->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            DB::table('trip_user')
                ->where('trip_id', $lockedTrip->getKey())
                ->orderBy('user_id')
                ->lockForUpdate()
                ->get();

            Gate::forUser($actor)->authorize('updateMemberRole', [$lockedTrip, $member, $role]);

            $lockedTrip->members()->updateExistingPivot($member->getKey(), ['role' => $role]);
        }, attempts: 3);
    }
}
