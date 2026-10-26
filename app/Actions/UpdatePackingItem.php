<?php

namespace App\Actions;

use App\Models\PackingItem;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class UpdatePackingItem
{
    /**
     * @param array{
     *     name?: string,
     *     quantity?: int,
     *     comment?: string|null,
     *     participant_id?: int|null
     * } $attributes
     */
    public function __invoke(User $actor, Trip $trip, int $packingItemId, array $attributes): PackingItem
    {
        Gate::forUser($actor)->authorize('update', $trip);

        $validated = Validator::make($attributes, [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'quantity' => ['sometimes', 'required', 'integer', 'min:1'],
            'comment' => ['sometimes', 'nullable', 'string'],
            'participant_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
        ])->validate();

        if (array_key_exists('participant_id', $validated) && $validated['participant_id'] !== null) {
            $trip->participants()->findOrFail($validated['participant_id']);
        }

        return DB::transaction(function () use ($trip, $packingItemId, $validated): PackingItem {
            $packingItem = $trip->packingItems()->findOrFail($packingItemId);
            $packingItem->update($validated);

            app(RefreshTripInspirationProjection::class)->handle($trip);

            return $packingItem;
        });
    }
}
