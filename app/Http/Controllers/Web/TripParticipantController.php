<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\DestroyTripParticipantRequest;
use App\Http\Requests\Web\StoreTripParticipantRequest;
use App\Http\Requests\Web\UpdateTripParticipantRequest;
use App\Models\Trip;
use Illuminate\Http\RedirectResponse;

class TripParticipantController extends Controller
{
    public function store(StoreTripParticipantRequest $request, Trip|string $trip): RedirectResponse
    {
        $currentTrip = $request->user()->currentTenant;

        abort_unless($currentTrip instanceof Trip, 404);
        $currentTrip->participants()->create($request->validated());

        return redirect()->route('trips.show', $currentTrip);
    }

    public function update(UpdateTripParticipantRequest $request, Trip|string $trip, string $participant): RedirectResponse
    {
        $currentTrip = $request->user()->currentTenant;

        abort_unless($currentTrip instanceof Trip, 404);
        $currentTrip->participants()->findOrFail($participant)->update($request->validated());

        return redirect()->route('trips.show', $currentTrip);
    }

    public function destroy(DestroyTripParticipantRequest $request, Trip|string $trip, string $participant): RedirectResponse
    {
        $currentTrip = $request->user()->currentTenant;

        abort_unless($currentTrip instanceof Trip, 404);
        $currentTrip->participants()->findOrFail($participant)->delete();

        return redirect()->route('trips.show', $currentTrip);
    }
}
