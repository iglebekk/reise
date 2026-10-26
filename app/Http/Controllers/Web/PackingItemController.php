<?php

namespace App\Http\Controllers\Web;

use App\Actions\CreatePackingItem;
use App\Actions\DeletePackingItem;
use App\Actions\UpdatePackingItem;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\DestroyPackingItemRequest;
use App\Http\Requests\Web\StorePackingItemRequest;
use App\Http\Requests\Web\UpdatePackingItemRequest;
use App\Models\Trip;
use Illuminate\Http\RedirectResponse;

class PackingItemController extends Controller
{
    public function store(StorePackingItemRequest $request, Trip|string $trip, CreatePackingItem $createPackingItem): RedirectResponse
    {
        $currentTrip = $request->user()->currentTenant;

        abort_unless($currentTrip instanceof Trip, 404);
        $createPackingItem($request->user(), $currentTrip, $request->validated());

        return redirect()->route('trips.show', $currentTrip);
    }

    public function update(
        UpdatePackingItemRequest $request,
        Trip|string $trip,
        string $packingItem,
        UpdatePackingItem $updatePackingItem,
    ): RedirectResponse {
        $currentTrip = $request->user()->currentTenant;

        abort_unless($currentTrip instanceof Trip, 404);
        $updatePackingItem($request->user(), $currentTrip, (int) $packingItem, $request->validated());

        return redirect()->route('trips.show', $currentTrip);
    }

    public function destroy(
        DestroyPackingItemRequest $request,
        Trip|string $trip,
        string $packingItem,
        DeletePackingItem $deletePackingItem,
    ): RedirectResponse {
        $currentTrip = $request->user()->currentTenant;

        abort_unless($currentTrip instanceof Trip, 404);
        $deletePackingItem($request->user(), $currentTrip, (int) $packingItem);

        return redirect()->route('trips.show', $currentTrip);
    }
}
