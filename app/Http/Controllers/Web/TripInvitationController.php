<?php

namespace App\Http\Controllers\Web;

use App\Actions\CreateTripInvitation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\DestroyTripInvitationRequest;
use App\Http\Requests\Web\StoreTripInvitationRequest;
use App\Models\Trip;
use Illuminate\Http\RedirectResponse;

class TripInvitationController extends Controller
{
    public function store(
        StoreTripInvitationRequest $request,
        Trip|string $trip,
        CreateTripInvitation $createTripInvitation,
    ): RedirectResponse {
        $currentTrip = $request->user()->currentTenant;

        abort_unless($currentTrip instanceof Trip, 404);
        $createTripInvitation(
            $request->user(),
            $currentTrip,
            $request->validated('email'),
            $request->validated('role'),
        );

        return redirect()->route('trips.show', $currentTrip)->with('trip_status', 'created');
    }

    public function destroy(
        DestroyTripInvitationRequest $request,
        Trip|string $trip,
        string $invitation,
    ): RedirectResponse {
        $currentTrip = $request->user()->currentTenant;

        abort_unless($currentTrip instanceof Trip, 404);
        $currentTrip->invitations()->findOrFail($invitation)->delete();

        return redirect()->route('trips.show', $currentTrip)->with('trip_status', 'revoked');
    }
}
