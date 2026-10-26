<?php

namespace App\Actions;

use App\Models\Trip;
use App\Models\User;

class SelectCurrentTenant
{
    public function __invoke(User $user, int $tripId): Trip
    {
        $trip = $user->trips()->findOrFail($tripId);

        $user->setCurrentTenant($trip);

        return $trip;
    }
}
