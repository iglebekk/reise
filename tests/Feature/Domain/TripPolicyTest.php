<?php

use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows members to update only their current trip', function () {
    $user = User::factory()->create();
    $trip = Trip::factory()->create();
    $otherTrip = Trip::factory()->create();

    $trip->members()->attach($user, ['role' => 'member']);
    $otherTrip->members()->attach($user, ['role' => 'member']);

    $user->setCurrentTenant($trip);

    expect($user->can('update', $trip))->toBeTrue()
        ->and($user->isDirty('current_tenant_id'))->toBeFalse();

    $user->setCurrentTenant($otherTrip);

    expect($user->can('update', $trip))->toBeFalse();
});

it('allows members to edit trips while reserving trip administration for admins', function () {
    $admin = User::factory()->create();
    $member = User::factory()->create();
    $trip = Trip::factory()->create();

    $trip->members()->attach($admin, ['role' => 'admin']);
    $trip->members()->attach($member, ['role' => 'member']);
    $admin->setCurrentTenant($trip);
    $member->setCurrentTenant($trip);

    expect($admin->can('update', $trip))->toBeTrue()
        ->and($member->can('update', $trip))->toBeTrue()
        ->and($admin->can('delete', $trip))->toBeTrue()
        ->and($admin->can('invite', $trip))->toBeTrue()
        ->and($member->can('delete', $trip))->toBeFalse()
        ->and($member->can('invite', $trip))->toBeFalse()
        ->and($member->can('updateMemberRole', [$trip, $admin, 'member']))->toBeFalse();
});

it('denies a current-tenant context to a user without trip membership', function () {
    $user = User::factory()->create();
    $trip = Trip::factory()->create();

    $user->setCurrentTenant($trip);

    expect($user->can('update', $trip))->toBeFalse()
        ->and($user->can('delete', $trip))->toBeFalse();
});

it('prevents demoting or removing the last admin', function () {
    $admin = User::factory()->create();
    $trip = Trip::factory()->create();

    $trip->members()->attach($admin, ['role' => 'admin']);
    $admin->setCurrentTenant($trip);

    expect($admin->can('updateMemberRole', [$trip, $admin, 'member']))->toBeFalse()
        ->and($admin->can('removeMember', [$trip, $admin]))->toBeFalse();
});

it('allows changing or removing an admin when another admin remains', function () {
    $admin = User::factory()->create();
    $otherAdmin = User::factory()->create();
    $trip = Trip::factory()->create();

    $trip->members()->attach($admin, ['role' => 'admin']);
    $trip->members()->attach($otherAdmin, ['role' => 'admin']);
    $admin->setCurrentTenant($trip);

    expect($admin->can('updateMemberRole', [$trip, $otherAdmin, 'member']))->toBeTrue()
        ->and($admin->can('removeMember', [$trip, $otherAdmin]))->toBeTrue();
});
