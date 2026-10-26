<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\ChangeTripMemberRole;
use App\Actions\RemoveTripMember;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\DestroyTripMemberRequest;
use App\Http\Requests\Api\V1\UpdateTripMemberRequest;
use App\Http\Resources\TripMemberResource;
use App\Models\Trip;
use Illuminate\Http\Resources\Json\JsonResource;
use Symfony\Component\HttpFoundation\Response;

class TripMemberController extends Controller
{
    public function update(
        UpdateTripMemberRequest $request,
        Trip|string $trip,
        string $user,
        ChangeTripMemberRole $changeRole,
    ): JsonResource {
        $currentTrip = $request->user()->currentTenant;
        $member = $currentTrip->members()->findOrFail($user);

        $changeRole($request->user(), $currentTrip, $member, $request->validated('role'));

        return new TripMemberResource($currentTrip->members()->findOrFail($user));
    }

    public function destroy(
        DestroyTripMemberRequest $request,
        Trip|string $trip,
        string $user,
        RemoveTripMember $removeMember,
    ): Response {
        $currentTrip = $request->user()->currentTenant;
        $member = $currentTrip->members()->findOrFail($user);

        $removeMember($request->user(), $currentTrip, $member);

        return response()->noContent();
    }
}
