<?php

use App\Http\Resources\PackingItemResource;
use App\Http\Resources\TripParticipantResource;
use App\Http\Resources\TripResource;
use App\Models\PackingItem;
use App\Models\Trip;
use App\Models\TripParticipant;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('trip resource serializes dates and inclusive duration without its creator id', function (): void {
    $trip = Trip::factory()->create([
        'start_date' => '2026-08-10',
        'end_date' => '2026-08-10',
    ]);

    $data = (new TripResource($trip))->resolve();

    expect($data)
        ->toHaveKey('id', $trip->id)
        ->toHaveKey('start_date', '2026-08-10')
        ->toHaveKey('end_date', '2026-08-10')
        ->toHaveKey('duration_days', 1)
        ->not->toHaveKey('created_by_user_id');
});

test('participant resource serializes participant fields without tenant or user ids', function (): void {
    $participant = TripParticipant::factory()->create([
        'name' => 'Alex Example',
        'age' => 12,
    ]);

    $data = (new TripParticipantResource($participant))->resolve();

    expect($data)
        ->toHaveKey('id', $participant->id)
        ->toHaveKey('name', 'Alex Example')
        ->toHaveKey('age', 12)
        ->not->toHaveKey('trip_id')
        ->not->toHaveKey('user_id');
});

test('packing item resource serializes a null participant id for the shared list', function (): void {
    $packingItem = PackingItem::factory()->create([
        'participant_id' => null,
        'name' => 'Sunscreen',
        'quantity' => 2,
        'comment' => 'SPF 50',
    ]);

    $data = (new PackingItemResource($packingItem))->resolve();

    expect($data)
        ->toHaveKey('id', $packingItem->id)
        ->toHaveKey('participant_id', null)
        ->toHaveKey('name', 'Sunscreen')
        ->toHaveKey('quantity', 2)
        ->toHaveKey('comment', 'SPF 50')
        ->not->toHaveKey('trip_id');
});
