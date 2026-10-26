<?php

namespace App\Actions;

use App\Models\Trip;
use App\Models\TripInspirationProjection;
use App\Services\InspirationCatalog;
use Illuminate\Support\Facades\DB;

class RefreshTripInspirationProjection
{
    public function handle(Trip $trip): ?TripInspirationProjection
    {
        return DB::transaction(function () use ($trip): ?TripInspirationProjection {
            $trip = Trip::query()->whereKey($trip->getKey())->lockForUpdate()->firstOrFail();

            if ($trip->end_date->isToday() || $trip->end_date->isFuture()) {
                $trip->inspirationProjection()->delete();

                return null;
            }

            if (! $this->hasAllowedCategories($trip)) {
                $trip->inspirationProjection()->delete();

                return null;
            }

            $durationInDays = $trip->durationInDays();

            if ($durationInDays < 1) {
                $trip->inspirationProjection()->delete();

                return null;
            }

            $projection = $trip->inspirationProjection()->firstOrNew();
            $projection->fill([
                'owner_id' => $trip->created_by_user_id,
                'trip_type' => $trip->type,
                'transport' => $trip->transport,
                'accommodation' => $trip->accommodation,
                'duration_group' => $this->durationGroup($durationInDays),
            ]);
            $projection->save();

            $canonicalNames = $trip->packingItems()
                ->pluck('name')
                ->map(fn (string $name): ?string => InspirationCatalog::canonicalItemName($name))
                ->filter()
                ->unique()
                ->values();

            $projection->items()->delete();
            $projection->items()->createMany($canonicalNames->map(
                fn (string $name): array => ['canonical_name' => $name],
            )->all());

            return $projection->load('items');
        }, attempts: 3);
    }

    private function hasAllowedCategories(Trip $trip): bool
    {
        return in_array($trip->type, InspirationCatalog::tripTypes(), true)
            && in_array($trip->transport, InspirationCatalog::transportOptions(), true)
            && in_array($trip->accommodation, InspirationCatalog::accommodationOptions(), true);
    }

    private function durationGroup(int $durationInDays): string
    {
        return match (true) {
            $durationInDays === 1 => 'day',
            $durationInDays <= 3 => 'weekend',
            $durationInDays <= 7 => 'week',
            default => 'extended',
        };
    }
}
