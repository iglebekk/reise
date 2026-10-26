<?php

namespace App\Console\Commands;

use App\Actions\RefreshTripInspirationProjection;
use App\Models\Trip;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;

#[Signature('inspiration:projections-refresh')]
#[Description('Refresh missing or stale historical trip inspiration projections')]
class RefreshInspirationProjections extends Command
{
    public function handle(RefreshTripInspirationProjection $refreshProjection): int
    {
        $refreshedCount = 0;

        Trip::query()
            ->select('trips.id')
            ->whereDate('trips.end_date', '<', today()->toDateString())
            ->where(function (Builder $query): void {
                $query->doesntHave('inspirationProjection')
                    ->orWhereHas('inspirationProjection', function (Builder $projectionQuery): void {
                        $projectionQuery
                            ->whereColumn('trip_inspiration_projections.owner_id', '!=', 'trips.created_by_user_id')
                            ->orWhereColumn('trip_inspiration_projections.trip_type', '!=', 'trips.type')
                            ->orWhereColumn('trip_inspiration_projections.transport', '!=', 'trips.transport')
                            ->orWhereColumn('trip_inspiration_projections.accommodation', '!=', 'trips.accommodation')
                            ->orWhereColumn('trip_inspiration_projections.updated_at', '<', 'trips.updated_at')
                            ->orWhereExists(function (QueryBuilder $itemQuery): void {
                                $itemQuery->selectRaw('1')
                                    ->from('packing_items')
                                    ->whereColumn('packing_items.trip_id', 'trips.id')
                                    ->whereColumn(
                                        'packing_items.updated_at',
                                        '>',
                                        'trip_inspiration_projections.updated_at',
                                    );
                            });
                    });
            })
            ->chunkById(100, function (Collection $trips) use ($refreshProjection, &$refreshedCount): void {
                foreach ($trips as $trip) {
                    $refreshProjection->handle($trip);
                    $refreshedCount++;
                }
            });

        $this->info("Processed {$refreshedCount} trip inspiration projection(s).");

        return self::SUCCESS;
    }
}
