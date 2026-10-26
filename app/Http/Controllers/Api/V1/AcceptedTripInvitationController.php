<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\AcceptTripInvitation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\AcceptTripInvitationRequest;
use App\Http\Resources\TripResource;

class AcceptedTripInvitationController extends Controller
{
    public function store(AcceptTripInvitationRequest $request, AcceptTripInvitation $acceptInvitation): TripResource
    {
        $trip = $acceptInvitation($request->user(), $request->validated('token'));

        return new TripResource($trip);
    }
}
