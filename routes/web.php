<?php

use App\Http\Controllers\Web\AcceptedTripInvitationController;
use App\Http\Controllers\Web\PackingItemController;
use App\Http\Controllers\Web\PendingTripInvitationController;
use App\Http\Controllers\Web\TripController;
use App\Http\Controllers\Web\TripInvitationController;
use App\Http\Controllers\Web\TripMemberController;
use App\Http\Controllers\Web\TripParticipantController;
use App\Http\Middleware\SelectTripTenant;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('trips.index'));

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('/trip-invitations/{token}', [PendingTripInvitationController::class, 'show'])
        ->name('trip-invitations.show');
    Route::post('/trip-invitations/accept', [AcceptedTripInvitationController::class, 'store'])
        ->name('trip-invitations.accept');
    Route::get('/trips', [TripController::class, 'index'])->name('trips.index');
    Route::get('/trips/create', [TripController::class, 'create'])->name('trips.create');
    Route::post('/trips', [TripController::class, 'store'])->name('trips.store');
    Route::get('/trips/{trip}', [TripController::class, 'show'])
        ->middleware(SelectTripTenant::class)
        ->name('trips.show');
    Route::resource('trips', TripController::class)
        ->only(['edit', 'update', 'destroy'])
        ->middleware(SelectTripTenant::class);
    Route::resource('trips.participants', TripParticipantController::class)
        ->only(['store', 'update', 'destroy'])
        ->middleware(SelectTripTenant::class);
    Route::resource('trips.packing-items', PackingItemController::class)
        ->only(['store', 'update', 'destroy'])
        ->middleware(SelectTripTenant::class);
    Route::resource('trips.invitations', TripInvitationController::class)
        ->only(['store', 'destroy'])
        ->middleware(SelectTripTenant::class);
    Route::resource('trips.members', TripMemberController::class)
        ->only(['update', 'destroy'])
        ->parameters(['members' => 'user'])
        ->middleware(SelectTripTenant::class);
});
