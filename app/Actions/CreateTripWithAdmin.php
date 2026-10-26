<?php

namespace App\Actions;

use App\Models\Trip;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class CreateTripWithAdmin
{
    /**
     * @param array{
     *     destination: string,
     *     start_date: string,
     *     end_date: string,
     *     type: string,
     *     transport: string,
     *     accommodation: string,
     *     activities?: array<int, string>,
     *     description?: string|null
     * } $attributes
     */
    public function __invoke(User $creator, array $attributes): Trip
    {
        Gate::forUser($creator)->authorize('create', Trip::class);

        return DB::transaction(function () use ($creator, $attributes): Trip {
            $trip = Trip::query()->create(array_merge($attributes, [
                'created_by_user_id' => $creator->getKey(),
            ]));

            $trip->members()->attach($creator, ['role' => 'admin']);

            app(RefreshTripInspirationProjection::class)->handle($trip);

            return $trip;
        });
    }
}
