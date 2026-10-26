<?php

use App\Models\PackingItem;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->travelTo(now()->setDate(2026, 10, 26)->startOfDay());
});

it('lets a member edit trip metadata while preserving partial fields and refreshing the projection', function () {
    $member = User::factory()->create();
    $trip = Trip::factory()->create([
        'destination' => 'Old destination',
        'start_date' => '2026-10-01',
        'end_date' => '2026-10-03',
        'type' => 'family',
        'description' => 'Keep this note.',
        'activities' => ['hiking'],
    ]);
    $trip->members()->attach($member, ['role' => 'member']);
    PackingItem::factory()->for($trip)->create(['name' => 'Phone']);

    $this->actingAs($member)
        ->get(route('trips.edit', $trip))
        ->assertOk()
        ->assertSee('Old destination')
        ->assertSee('Keep this note.');

    $this->patch(route('trips.update', $trip), ['destination' => 'New destination'])
        ->assertRedirect(route('trips.show', $trip));

    $this->assertDatabaseHas('trips', [
        'id' => $trip->getKey(),
        'destination' => 'New destination',
        'description' => 'Keep this note.',
        'type' => 'family',
    ]);
    expect($trip->fresh()->end_date->toDateString())->toBe('2026-10-03');

    $this->patch(route('trips.update', $trip), [
        'type' => 'business',
        'start_date' => '2026-10-05',
        'end_date' => '2026-10-05',
    ])->assertRedirect(route('trips.show', $trip));

    $projection = $trip->inspirationProjection()->with('items')->firstOrFail();
    expect($projection->trip_type)->toBe('business')
        ->and($projection->duration_group)->toBe('day')
        ->and($projection->items->pluck('canonical_name')->all())->toBe(['Phone']);
    $this->assertDatabaseHas('trips', [
        'id' => $trip->getKey(),
        'description' => 'Keep this note.',
        'destination' => 'New destination',
    ]);
});

it('returns visible validation errors for invalid dates and catalog values', function () {
    $member = User::factory()->create();
    $trip = Trip::factory()->create();
    $trip->members()->attach($member, ['role' => 'member']);

    $this->actingAs($member)
        ->followingRedirects()
        ->from(route('trips.edit', $trip))
        ->patch(route('trips.update', $trip), [
            'start_date' => 'not-a-date',
            'type' => 'private',
        ])
        ->assertOk()
        ->assertSee('Please review the highlighted fields and try again.');

    $this->actingAs($member)
        ->patchJson(route('trips.update', $trip), [
            'start_date' => 'not-a-date',
            'type' => 'private',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['start_date', 'type']);

    $this->patchJson(route('trips.update', $trip), [
        'start_date' => '2026-11-12',
        'end_date' => '2026-11-11',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('end_date');

    $this->assertDatabaseHas('trips', [
        'id' => $trip->getKey(),
        'type' => $trip->type,
    ]);
});

it('shows edit to members and delete only to admins', function () {
    $admin = User::factory()->create();
    $member = User::factory()->create();
    $trip = Trip::factory()->create();
    $trip->members()->attach($admin, ['role' => 'admin']);
    $trip->members()->attach($member, ['role' => 'member']);

    $this->actingAs($member)
        ->get(route('trips.show', $trip))
        ->assertOk()
        ->assertSee('Edit trip')
        ->assertDontSee('Delete trip');

    $this->actingAs($admin)
        ->get(route('trips.show', $trip))
        ->assertOk()
        ->assertSee('Edit trip')
        ->assertSee('Delete trip');
});

it('allows member edits but reserves trip deletion for admins', function () {
    $admin = User::factory()->create();
    $member = User::factory()->create();
    $trip = Trip::factory()->create();
    $trip->members()->attach($admin, ['role' => 'admin']);
    $trip->members()->attach($member, ['role' => 'member']);

    $this->actingAs($member)
        ->patch(route('trips.update', $trip), ['destination' => 'Changed by member'])
        ->assertRedirect(route('trips.show', $trip));
    $this->delete(route('trips.destroy', $trip))->assertForbidden();
    $this->assertDatabaseHas('trips', ['id' => $trip->getKey()]);

    $this->actingAs($admin)
        ->delete(route('trips.destroy', $trip))
        ->assertRedirect(route('trips.index'));
    $this->assertDatabaseMissing('trips', ['id' => $trip->getKey()]);
});

it('returns not found when a user selects another trip for editing', function () {
    $member = User::factory()->create();
    $trip = Trip::factory()->create();
    $otherTrip = Trip::factory()->create();
    $trip->members()->attach($member, ['role' => 'member']);

    $this->actingAs($member)
        ->get(route('trips.edit', $otherTrip))
        ->assertNotFound();
});
