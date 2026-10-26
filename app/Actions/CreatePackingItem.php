<?php

namespace App\Actions;

use App\Models\PackingItem;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class CreatePackingItem
{
    /**
     * @param array{
     *     name: string,
     *     quantity?: int,
     *     comment?: string|null,
     *     participant_id?: int|null
     * } $attributes
     */
    public function __invoke(User $actor, Trip $trip, array $attributes): PackingItem
    {
        Gate::forUser($actor)->authorize('update', $trip);

        $validated = Validator::make($attributes, [
            'name' => ['required', 'string', 'max:255'],
            'quantity' => ['sometimes', 'required', 'integer', 'min:1'],
            'comment' => ['sometimes', 'nullable', 'string'],
            'participant_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
        ])->validate();

        $participantId = $validated['participant_id'] ?? null;

        if ($participantId !== null) {
            $trip->participants()->findOrFail($participantId);
        }

        return DB::transaction(function () use ($trip, $validated, $participantId): PackingItem {
            $packingItem = $trip->packingItems()->create([
                'participant_id' => $participantId,
                'name' => $validated['name'],
                'quantity' => $validated['quantity'] ?? 1,
                'comment' => $validated['comment'] ?? null,
            ]);

            app(RefreshTripInspirationProjection::class)->handle($trip);

            return $packingItem;
        });
    }
}
