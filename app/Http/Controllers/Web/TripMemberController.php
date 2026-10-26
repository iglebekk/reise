<?php

namespace App\Http\Controllers\Web;

use App\Actions\ChangeTripMemberRole;
use App\Actions\RemoveTripMember;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\DestroyTripMemberRequest;
use App\Http\Requests\Web\UpdateTripMemberRequest;
use App\Models\Trip;
use Illuminate\Http\RedirectResponse;

class TripMemberController extends Controller
{
    public function update(
        UpdateTripMemberRequest $request,
        Trip|string $trip,
        string $user,
        ChangeTripMemberRole $changeTripMemberRole,
    ): RedirectResponse {
        $currentTrip = $request->user()->currentTenant;

        abort_unless($currentTrip instanceof Trip, 404);
        $member = $currentTrip->members()->findOrFail($user);
        $changeTripMemberRole($request->user(), $currentTrip, $member, $request->validated('role'));

        return redirect()->route('trips.show', $currentTrip)->with('trip_status', 'member-role-updated');
    }

    public function destroy(
        DestroyTripMemberRequest $request,
        Trip|string $trip,
        string $user,
        RemoveTripMember $removeTripMember,
    ): RedirectResponse {
        $currentTrip = $request->user()->currentTenant;

        abort_unless($currentTrip instanceof Trip, 404);
        $member = $currentTrip->members()->findOrFail($user);
        $removeTripMember($request->user(), $currentTrip, $member);

        return redirect()->route('trips.show', $currentTrip)->with('trip_status', 'member-removed');
    }
}
