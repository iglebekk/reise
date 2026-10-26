<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\DestroyTripParticipantRequest;
use App\Http\Requests\Api\V1\StoreTripParticipantRequest;
use App\Http\Requests\Api\V1\UpdateTripParticipantRequest;
use App\Http\Resources\TripParticipantResource;
use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class TripParticipantController extends Controller
{
    public function index(Request $request, Trip|string $trip): AnonymousResourceCollection
    {
        return TripParticipantResource::collection($request->user()->currentTenant->participants()->get());
    }

    public function store(StoreTripParticipantRequest $request, Trip|string $trip): TripParticipantResource
    {
        $participant = $request->user()->currentTenant->participants()->create($request->validated());

        return new TripParticipantResource($participant);
    }

    public function show(Request $request, Trip|string $trip, string $participant): TripParticipantResource
    {
        $participant = $request->user()->currentTenant->participants()->findOrFail($participant);

        return new TripParticipantResource($participant);
    }

    public function update(
        UpdateTripParticipantRequest $request,
        Trip|string $trip,
        string $participant,
    ): TripParticipantResource {
        $participant = $request->user()->currentTenant->participants()->findOrFail($participant);
        $participant->update($request->validated());

        return new TripParticipantResource($participant);
    }

    public function destroy(DestroyTripParticipantRequest $request, Trip|string $trip, string $participant): Response
    {
        $request->user()->currentTenant->participants()->findOrFail($participant)->delete();

        return response()->noContent();
    }
}
