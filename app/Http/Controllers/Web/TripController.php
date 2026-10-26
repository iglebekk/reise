<?php

namespace App\Http\Controllers\Web;

use App\Actions\CreateTripWithAdmin;
use App\Actions\DeleteTrip;
use App\Actions\UpdateTrip;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\DestroyTripRequest;
use App\Http\Requests\Web\StoreTripRequest;
use App\Http\Requests\Web\UpdateTripRequest;
use App\Models\Trip;
use App\Services\InspirationCatalog;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TripController extends Controller
{
    public function index(Request $request): View
    {
        $trips = $request->user()->trips()->orderBy('start_date')->get();

        return view('trips.index', ['trips' => $trips]);
    }

    public function create(): View
    {
        return view('trips.create', [
            'tripTypes' => InspirationCatalog::tripTypes(),
            'transportOptions' => InspirationCatalog::transportOptions(),
            'accommodationOptions' => InspirationCatalog::accommodationOptions(),
            'activityOptions' => InspirationCatalog::activityOptions(),
        ]);
    }

    public function store(StoreTripRequest $request, CreateTripWithAdmin $createTrip): RedirectResponse
    {
        $trip = $createTrip($request->user(), $request->validated());

        return redirect()->route('trips.show', $trip);
    }

    public function show(Request $request): View
    {
        $trip = $request->user()->currentTenant;

        abort_unless($trip instanceof Trip, 404);

        $trip->load([
            'members',
            'participants' => fn (Relation $query) => $query->orderBy('name'),
            'participants.packingItems' => fn (Relation $query) => $query->orderBy('name'),
            'packingItems' => fn (Relation $query) => $query->whereNull('participant_id')->orderBy('name'),
        ]);

        if ($request->user()->can('invite', $trip)) {
            $trip->load(['invitations' => fn (Relation $query) => $query->orderByDesc('created_at')]);
        }

        return view('trips.show', ['trip' => $trip]);
    }

    public function edit(Request $request): View
    {
        $trip = $request->user()->currentTenant;

        abort_unless($trip instanceof Trip, 404);

        return view('trips.edit', [
            'trip' => $trip,
            'tripTypes' => InspirationCatalog::tripTypes(),
            'transportOptions' => InspirationCatalog::transportOptions(),
            'accommodationOptions' => InspirationCatalog::accommodationOptions(),
            'activityOptions' => InspirationCatalog::activityOptions(),
        ]);
    }

    public function update(
        UpdateTripRequest $request,
        Trip|string $trip,
        UpdateTrip $updateTrip,
    ): RedirectResponse {
        $currentTrip = $request->user()->currentTenant;

        abort_unless($currentTrip instanceof Trip, 404);
        $updatedTrip = $updateTrip($request->user(), $currentTrip, $request->validated());

        return redirect()->route('trips.show', $updatedTrip)->with('trip_status', 'updated');
    }

    public function destroy(
        DestroyTripRequest $request,
        Trip|string $trip,
        DeleteTrip $deleteTrip,
    ): RedirectResponse {
        $currentTrip = $request->user()->currentTenant;

        abort_unless($currentTrip instanceof Trip, 404);
        $deleteTrip($request->user(), $currentTrip);

        return redirect()->route('trips.index')->with('trip_status', 'deleted');
    }
}
