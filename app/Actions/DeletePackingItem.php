<?php

namespace App\Actions;

use App\Models\Trip;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class DeletePackingItem
{
    public function __invoke(User $actor, Trip $trip, int $packingItemId): void
    {
        Gate::forUser($actor)->authorize('update', $trip);

        DB::transaction(function () use ($trip, $packingItemId): void {
            $trip->packingItems()->findOrFail($packingItemId)->delete();

            app(RefreshTripInspirationProjection::class)->handle($trip);
        });
    }
}
