<?php

namespace App\Services;

use App\Models\PackingItem;
use App\Models\Trip;
use App\Models\TripInspirationProjection;
use App\Models\TripParticipant;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SearchSimilarTrips
{
    /**
     * @return list<array<string, mixed>>
     */
    public function handle(User $user): array
    {
        $selectedTrip = $user->currentTenant;

        if (! $selectedTrip instanceof Trip || ! $user->trips()->whereKey($selectedTrip->getKey())->exists()) {
            return [];
        }

        $pastTrips = $user->trips()
            ->with(['participants.packingItems', 'packingItems'])
            ->where('trips.end_date', '<', today()->toDateString())
            ->whereKeyNot($selectedTrip->getKey())
            ->get();

        $rankedResults = $pastTrips->map(fn (Trip $trip): array => [
            'score' => $this->ownTripScore($selectedTrip, $trip),
            'result' => [
                'source' => 'own',
                'trip' => $this->tripDetails($trip),
            ],
        ])->all();

        $membershipTripIds = DB::table('trip_user')
            ->where('user_id', $user->getKey())
            ->pluck('trip_id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        $projectionQuery = TripInspirationProjection::query()
            ->with('items')
            ->where('owner_id', '!=', $user->getKey());

        if ($membershipTripIds !== []) {
            $projectionQuery->whereNotIn('trip_id', $membershipTripIds);
        }

        $groups = $projectionQuery->get()->groupBy(fn (TripInspirationProjection $projection): string => implode('|', [
            $projection->trip_type,
            $projection->transport,
            $projection->accommodation,
            $projection->duration_group,
        ]));

        foreach ($groups as $projections) {
            $owners = $projections->pluck('owner_id')->unique();

            if ($owners->count() < 3) {
                continue;
            }

            /** @var TripInspirationProjection $projection */
            $projection = $projections->first();
            $categories = [
                'type' => $projection->trip_type,
                'transport' => $projection->transport,
                'accommodation' => $projection->accommodation,
                'duration_group' => $projection->duration_group,
            ];
            $itemNames = $projections->flatMap(fn (TripInspirationProjection $groupProjection) => $groupProjection->items)
                ->pluck('canonical_name')
                ->unique()
                ->sort()
                ->values()
                ->all();

            $rankedResults[] = [
                'score' => $this->categoryScore($selectedTrip, $categories),
                'result' => [
                    'source' => 'anonymous',
                    'categories' => $categories,
                    'item_names' => $itemNames,
                ],
            ];
        }

        usort($rankedResults, fn (array $left, array $right): int => $right['score'] <=> $left['score']);

        return array_values(array_map(
            fn (array $rankedResult): array => $rankedResult['result'],
            array_slice($rankedResults, 0, 10),
        ));
    }

    private function ownTripScore(Trip $selectedTrip, Trip $candidateTrip): int
    {
        $selectedActivities = $selectedTrip->activities ?? [];
        $candidateActivities = $candidateTrip->activities ?? [];
        $activityOverlap = count(array_intersect($selectedActivities, $candidateActivities));

        return ($selectedTrip->type === $candidateTrip->type ? 3 : 0)
            + $activityOverlap
            + ($this->durationGroup($selectedTrip) === $this->durationGroup($candidateTrip) ? 2 : 0)
            + ($selectedTrip->transport === $candidateTrip->transport ? 1 : 0)
            + ($selectedTrip->accommodation === $candidateTrip->accommodation ? 1 : 0)
            + (mb_strtolower(trim($selectedTrip->destination)) === mb_strtolower(trim($candidateTrip->destination)) ? 2 : 0);
    }

    /**
     * @param  array{type: string, transport: string, accommodation: string, duration_group: string}  $categories
     */
    private function categoryScore(Trip $trip, array $categories): int
    {
        return ($trip->type === $categories['type'] ? 3 : 0)
            + ($trip->transport === $categories['transport'] ? 1 : 0)
            + ($trip->accommodation === $categories['accommodation'] ? 1 : 0)
            + ($this->durationGroup($trip) === $categories['duration_group'] ? 2 : 0);
    }

    private function durationGroup(Trip $trip): string
    {
        $duration = $trip->durationInDays();

        return match (true) {
            $duration === 1 => 'day',
            $duration <= 3 => 'weekend',
            $duration <= 7 => 'week',
            default => 'extended',
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function tripDetails(Trip $trip): array
    {
        return [
            'id' => $trip->id,
            'destination' => $trip->destination,
            'start_date' => $trip->start_date->toDateString(),
            'end_date' => $trip->end_date->toDateString(),
            'duration_days' => $trip->durationInDays(),
            'type' => $trip->type,
            'transport' => $trip->transport,
            'accommodation' => $trip->accommodation,
            'activities' => $trip->activities,
            'description' => $trip->description,
            'participants' => $trip->participants->map(fn (TripParticipant $participant): array => [
                'id' => $participant->id,
                'name' => $participant->name,
                'age' => $participant->age,
                'user_id' => $participant->user_id,
                'packing_items' => $participant->packingItems->map(fn (PackingItem $item): array => $this->packingItemDetails($item))->all(),
            ])->all(),
            'packing_items' => $trip->packingItems->map(fn (PackingItem $item): array => $this->packingItemDetails($item))->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function packingItemDetails(PackingItem $item): array
    {
        return [
            'id' => $item->id,
            'participant_id' => $item->participant_id,
            'name' => $item->name,
            'quantity' => $item->quantity,
            'comment' => $item->comment,
        ];
    }
}
