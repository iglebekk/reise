<?php

use App\Actions\CreateTripWithAdmin;
use App\Actions\SelectCurrentTenant;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates a trip with its creator as admin', function () {
    $creator = User::factory()->create();

    $trip = (new CreateTripWithAdmin)($creator, [
        'destination' => 'Bergen',
        'start_date' => '2026-10-25',
        'end_date' => '2026-10-25',
        'type' => 'family',
        'transport' => 'train',
        'accommodation' => 'hotel',
        'activities' => [],
        'description' => null,
    ]);

    expect($trip->creator->is($creator))->toBeTrue()
        ->and($trip->durationInDays())->toBe(1)
        ->and($trip->members()->whereKey($creator->getKey())->first()?->pivot?->role)->toBe('admin');

    $this->assertDatabaseHas('trip_user', [
        'trip_id' => $trip->getKey(),
        'user_id' => $creator->getKey(),
        'role' => 'admin',
    ]);
});

it('selects a trip only through the user membership relation', function () {
    $user = User::factory()->create();
    $trip = Trip::factory()->create();
    $trip->members()->attach($user, ['role' => 'member']);

    $selectedTrip = (new SelectCurrentTenant)($user, $trip->getKey());

    expect($selectedTrip->is($trip))->toBeTrue()
        ->and($user->currentTenant->is($trip))->toBeTrue()
        ->and($user->current_tenant_id)->toBe($trip->getKey())
        ->and($user->isDirty('current_tenant_id'))->toBeFalse()
        ->and($user->fresh()->current_tenant_id)->toBeNull();
});

it('returns not found when the user is not a member of the selected trip', function () {
    $user = User::factory()->create();
    $trip = Trip::factory()->create();

    expect(fn () => (new SelectCurrentTenant)($user, $trip->getKey()))
        ->toThrow(ModelNotFoundException::class);
});
