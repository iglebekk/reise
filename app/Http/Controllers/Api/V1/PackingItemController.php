<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\CreatePackingItem;
use App\Actions\DeletePackingItem;
use App\Actions\UpdatePackingItem;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\DestroyPackingItemRequest;
use App\Http\Requests\Api\V1\StorePackingItemRequest;
use App\Http\Requests\Api\V1\UpdatePackingItemRequest;
use App\Http\Resources\PackingItemResource;
use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class PackingItemController extends Controller
{
    public function index(Request $request, Trip|string $trip): AnonymousResourceCollection
    {
        return PackingItemResource::collection($request->user()->currentTenant->packingItems()->get());
    }

    public function store(
        StorePackingItemRequest $request,
        Trip|string $trip,
        CreatePackingItem $createPackingItem,
    ): PackingItemResource {
        $item = $createPackingItem(
            $request->user(),
            $request->user()->currentTenant,
            $request->validated(),
        );

        return new PackingItemResource($item);
    }

    public function show(Request $request, Trip|string $trip, string $packingItem): PackingItemResource
    {
        $item = $request->user()->currentTenant->packingItems()->findOrFail($packingItem);

        return new PackingItemResource($item);
    }

    public function update(
        UpdatePackingItemRequest $request,
        Trip|string $trip,
        string $packingItem,
        UpdatePackingItem $updatePackingItem,
    ): PackingItemResource {
        $item = $updatePackingItem(
            $request->user(),
            $request->user()->currentTenant,
            (int) $packingItem,
            $request->validated(),
        );

        return new PackingItemResource($item);
    }

    public function destroy(
        DestroyPackingItemRequest $request,
        Trip|string $trip,
        string $packingItem,
        DeletePackingItem $deletePackingItem,
    ): Response {
        $deletePackingItem($request->user(), $request->user()->currentTenant, (int) $packingItem);

        return response()->noContent();
    }
}
