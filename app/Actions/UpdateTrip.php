<?php

namespace App\Actions;

use App\Models\Trip;
use App\Models\User;
use App\Services\InspirationCatalog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UpdateTrip
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __invoke(User $actor, Trip $trip, array $attributes): Trip
    {
        Gate::forUser($actor)->authorize('update', $trip);

        $mergedAttributes = array_replace([
            'destination' => $trip->destination,
            'start_date' => $trip->start_date->toDateString(),
            'end_date' => $trip->end_date->toDateString(),
            'type' => $trip->type,
            'transport' => $trip->transport,
            'accommodation' => $trip->accommodation,
            'activities' => $trip->activities ?? [],
            'description' => $trip->description,
        ], $attributes);

        $validatedAttributes = Validator::make($mergedAttributes, [
            'destination' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'type' => ['required', 'string', Rule::in(InspirationCatalog::tripTypes())],
            'transport' => ['required', 'string', Rule::in(InspirationCatalog::transportOptions())],
            'accommodation' => ['required', 'string', Rule::in(InspirationCatalog::accommodationOptions())],
            'activities' => ['present', 'array'],
            'activities.*' => ['required', 'string', Rule::in(InspirationCatalog::activityOptions())],
            'description' => ['nullable', 'string'],
        ])->validate();

        $updates = array_intersect_key($validatedAttributes, $attributes);

        return DB::transaction(function () use ($trip, $updates): Trip {
            $trip->fill($updates)->save();

            app(RefreshTripInspirationProjection::class)->handle($trip);

            return $trip->refresh();
        });
    }
}
