<?php

namespace App\Http\Middleware;

use App\Actions\SelectCurrentTenant;
use App\Models\Trip;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SelectTripTenant
{
    public function __construct(private SelectCurrentTenant $selectCurrentTenant) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        $routeTrip = $request->route('trip');
        $tripId = $routeTrip instanceof Trip ? (int) $routeTrip->getKey() : (int) $routeTrip;
        $trip = ($this->selectCurrentTenant)($user, $tripId);

        $request->route()->setParameter('trip', $trip);

        return $next($request);
    }
}
