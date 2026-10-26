<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\CreateTripWithAdmin;
use App\Actions\DeleteTrip;
use App\Actions\UpdateTrip;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\DestroyTripRequest;
use App\Http\Requests\Api\V1\StoreTripRequest;
use App\Http\Requests\Api\V1\UpdateTripRequest;
use App\Http\Resources\TripResource;
use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class TripController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        return TripResource::collection($request->user()->trips()->get());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTripRequest $request, CreateTripWithAdmin $createTrip): TripResource
    {
        return new TripResource($createTrip($request->user(), $request->validated()));
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request): TripResource
    {
        return new TripResource($request->user()->currentTenant);
    }

    public function update(UpdateTripRequest $request, Trip|string $trip, UpdateTrip $updateTrip): TripResource
    {
        $trip = $updateTrip($request->user(), $request->user()->currentTenant, $request->validated());

        return new TripResource($trip);
    }

    public function destroy(DestroyTripRequest $request, Trip|string $trip, DeleteTrip $deleteTrip): Response
    {
        $deleteTrip($request->user(), $request->user()->currentTenant);

        return response()->noContent();
    }
}
