<?php

namespace App\Actions;

use App\Models\Trip;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class DeleteTrip
{
    public function __invoke(User $actor, Trip $trip): void
    {
        Gate::forUser($actor)->authorize('delete', $trip);

        DB::transaction(function () use ($trip): void {
            $trip->delete();
        });
    }
}
