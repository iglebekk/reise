<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\CreateTripInvitation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\DestroyTripInvitationRequest;
use App\Http\Requests\Api\V1\StoreTripInvitationRequest;
use App\Http\Resources\TripInvitationResource;
use App\Models\Trip;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class TripInvitationController extends Controller
{
    public function store(StoreTripInvitationRequest $request, Trip|string $trip, CreateTripInvitation $createInvitation): JsonResponse
    {
        $invitation = $createInvitation(
            $request->user(),
            $request->user()->currentTenant,
            $request->validated('email'),
            $request->validated('role'),
        );

        return (new TripInvitationResource($invitation))->response()->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DestroyTripInvitationRequest $request, Trip|string $trip, string $invitation): Response
    {
        $request->user()->currentTenant->invitations()->findOrFail($invitation)->delete();

        return response()->noContent();
    }
}
