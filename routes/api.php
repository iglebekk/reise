<?php

use App\Http\Controllers\Api\V1\AcceptedTripInvitationController;
use App\Http\Controllers\Api\V1\PackingItemController;
use App\Http\Controllers\Api\V1\SimilarTripController;
use App\Http\Controllers\Api\V1\TripController;
use App\Http\Controllers\Api\V1\TripInvitationController;
use App\Http\Controllers\Api\V1\TripMemberController;
use App\Http\Controllers\Api\V1\TripParticipantController;
use App\Http\Middleware\SelectTripTenant;
use Illuminate\Support\Facades\Route;

Route::name('api.v1.')->middleware('auth:api')->group(function (): void {
    Route::post('invitations/accept', [AcceptedTripInvitationController::class, 'store'])->name('invitations.accept');

    Route::apiResource('trips', TripController::class)
        ->only(['index', 'store', 'show', 'update', 'destroy'])
        ->middlewareFor(['show', 'update', 'destroy'], SelectTripTenant::class);

    Route::apiResource('trips.participants', TripParticipantController::class)
        ->only(['index', 'store', 'show', 'update', 'destroy'])
        ->middlewareFor(['index', 'store', 'show', 'update', 'destroy'], SelectTripTenant::class);

    Route::apiResource('trips.packing-items', PackingItemController::class)
        ->only(['index', 'store', 'show', 'update', 'destroy'])
        ->middlewareFor(['index', 'store', 'show', 'update', 'destroy'], SelectTripTenant::class);

    Route::apiResource('trips.invitations', TripInvitationController::class)
        ->only(['store', 'destroy'])
        ->middlewareFor(['store', 'destroy'], SelectTripTenant::class);

    Route::apiResource('trips.members', TripMemberController::class)
        ->only(['update', 'destroy'])
        ->parameters(['members' => 'user'])
        ->middlewareFor(['update', 'destroy'], SelectTripTenant::class);

    Route::apiResource('trips.similar', SimilarTripController::class)
        ->only(['index'])
        ->middlewareFor('index', SelectTripTenant::class);
});
