<?php

namespace App\Http\Controllers\Web;

use App\Actions\AcceptTripInvitation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\AcceptTripInvitationRequest;
use Illuminate\Http\RedirectResponse;

class AcceptedTripInvitationController extends Controller
{
    public function store(
        AcceptTripInvitationRequest $request,
        AcceptTripInvitation $acceptTripInvitation,
    ): RedirectResponse {
        $trip = $acceptTripInvitation($request->user(), $request->validated('token'));

        return redirect()->route('trips.show', $trip);
    }
}
