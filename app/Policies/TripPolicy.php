<?php

namespace App\Policies;

use App\Models\Trip;
use App\Models\User;

class TripPolicy
{
    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Trip $trip): bool
    {
        return in_array($this->currentRole($user, $trip), ['admin', 'member'], true);
    }

    public function delete(User $user, Trip $trip): bool
    {
        return $this->currentRole($user, $trip) === 'admin';
    }

    public function invite(User $user, Trip $trip): bool
    {
        return $this->currentRole($user, $trip) === 'admin';
    }

    public function updateMemberRole(User $user, Trip $trip, User $member, string $role): bool
    {
        if ($this->currentRole($user, $trip) !== 'admin' || ! in_array($role, ['admin', 'member'], true)) {
            return false;
        }

        $memberRole = $this->memberRole($trip, $member);

        if ($memberRole === null) {
            return false;
        }

        return $memberRole !== 'admin' || $role !== 'member' || $this->adminCount($trip) > 1;
    }

    public function removeMember(User $user, Trip $trip, User $member): bool
    {
        if ($this->currentRole($user, $trip) !== 'admin') {
            return false;
        }

        $memberRole = $this->memberRole($trip, $member);

        if ($memberRole === null) {
            return false;
        }

        return $memberRole !== 'admin' || $this->adminCount($trip) > 1;
    }

    private function currentRole(User $user, Trip $trip): ?string
    {
        if ($user->current_tenant_id !== $trip->getKey()) {
            return null;
        }

        return $this->memberRole($trip, $user);
    }

    private function memberRole(Trip $trip, User $member): ?string
    {
        return $trip->members()
            ->whereKey($member->getKey())
            ->first()
            ?->pivot
            ?->role;
    }

    private function adminCount(Trip $trip): int
    {
        return $trip->members()->wherePivot('role', 'admin')->count();
    }
}
