<?php

use App\Actions\ChangeTripMemberRole;
use App\Actions\RemoveTripMember;
use App\Actions\SelectCurrentTenant;
use App\Models\PackingItem;
use App\Models\Trip;
use App\Models\TripParticipant;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('promotes a trip member to admin', function () {
    $admin = User::factory()->create();
    $member = User::factory()->create();
    $trip = Trip::factory()->create();
    $trip->members()->attach($admin, ['role' => 'admin']);
    $trip->members()->attach($member, ['role' => 'member']);
    $admin->setCurrentTenant($trip);

    (new ChangeTripMemberRole)($admin, $trip, $member, 'admin');

    expect($trip->members()->whereKey($member->getKey())->first()?->pivot?->role)->toBe('admin');
});

it('denies a member from changing or removing membership', function () {
    $member = User::factory()->create();
    $otherMember = User::factory()->create();
    $trip = Trip::factory()->create();
    $trip->members()->attach($member, ['role' => 'member']);
    $trip->members()->attach($otherMember, ['role' => 'member']);
    $member->setCurrentTenant($trip);

    expect(fn () => (new ChangeTripMemberRole)($member, $trip, $otherMember, 'admin'))
        ->toThrow(AuthorizationException::class);

    expect(fn () => (new RemoveTripMember)($member, $trip, $otherMember))
        ->toThrow(AuthorizationException::class);

    expect($trip->members()->count())->toBe(2);
});

it('prevents demoting or removing the last admin inside the action', function () {
    $admin = User::factory()->create();
    $trip = Trip::factory()->create();
    $trip->members()->attach($admin, ['role' => 'admin']);
    $admin->setCurrentTenant($trip);

    expect(fn () => (new ChangeTripMemberRole)($admin, $trip, $admin, 'member'))
        ->toThrow(AuthorizationException::class);

    expect(fn () => (new RemoveTripMember)($admin, $trip, $admin))
        ->toThrow(AuthorizationException::class);

    expect($trip->members()->whereKey($admin->getKey())->first()?->pivot?->role)->toBe('admin');
});

it('allows admin changes while at least one other admin remains', function () {
    $admin = User::factory()->create();
    $otherAdmin = User::factory()->create();
    $remainingAdmin = User::factory()->create();
    $trip = Trip::factory()->create();
    $trip->members()->attach($admin, ['role' => 'admin']);
    $trip->members()->attach($otherAdmin, ['role' => 'admin']);
    $trip->members()->attach($remainingAdmin, ['role' => 'admin']);
    $admin->setCurrentTenant($trip);

    (new ChangeTripMemberRole)($admin, $trip, $otherAdmin, 'member');
    (new RemoveTripMember)($admin, $trip, $remainingAdmin);

    expect($trip->members()->whereKey($otherAdmin->getKey())->first()?->pivot?->role)->toBe('member')
        ->and($trip->members()->whereKey($remainingAdmin->getKey())->exists())->toBeFalse()
        ->and($trip->members()->wherePivot('role', 'admin')->count())->toBe(1);
});

it('removes access without deleting participants or their packing items', function () {
    $admin = User::factory()->create();
    $member = User::factory()->create();
    $trip = Trip::factory()->create();
    $trip->members()->attach($admin, ['role' => 'admin']);
    $trip->members()->attach($member, ['role' => 'member']);
    $admin->setCurrentTenant($trip);
    $participant = TripParticipant::factory()->for($trip)->create(['user_id' => $member->getKey()]);
    $packingItem = PackingItem::factory()->for($trip)->create(['participant_id' => $participant->getKey()]);

    (new RemoveTripMember)($admin, $trip, $member);

    expect($trip->members()->whereKey($member->getKey())->exists())->toBeFalse();
    $this->assertModelExists($participant);
    $this->assertModelExists($packingItem);

    expect(fn () => (new SelectCurrentTenant)($member->fresh(), $trip->getKey()))
        ->toThrow(ModelNotFoundException::class);
});
