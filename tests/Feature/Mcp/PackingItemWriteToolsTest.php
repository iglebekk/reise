<?php

use App\Mcp\Servers\TripServer;
use App\Mcp\Tools\CreatePackingItemTool;
use App\Mcp\Tools\DeletePackingItemTool;
use App\Mcp\Tools\UpdatePackingItemTool;
use App\Models\PackingItem;
use App\Models\Trip;
use App\Models\TripParticipant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('packing item tools are registered with explicit destructive annotation on delete', function (): void {
    TripServer::actingAs(User::factory()->create())
        ->tools()
        ->assertRegistered([
            CreatePackingItemTool::class,
            UpdatePackingItemTool::class,
            DeletePackingItemTool::class,
        ]);

    expect((new CreatePackingItemTool)->name())->toBe('create_packing_item')
        ->and((new UpdatePackingItemTool)->name())->toBe('update_packing_item')
        ->and((new DeletePackingItemTool)->name())->toBe('delete_packing_item')
        ->and((new DeletePackingItemTool)->toArray()['annotations']['destructiveHint'])->toBeTrue();
});

test('a trip member can create update and delete a packing item while preserving omitted fields', function (): void {
    $user = User::factory()->create();
    $trip = Trip::factory()->create();
    $trip->members()->attach($user, ['role' => 'member']);
    $participant = TripParticipant::factory()->create(['trip_id' => $trip->id]);

    TripServer::actingAs($user)
        ->tool(CreatePackingItemTool::class, [
            'trip_id' => $trip->id,
            'name' => 'Water bottle',
            'quantity' => 2,
            'comment' => 'Bring a reusable one',
            'participant_id' => $participant->id,
        ])
        ->assertOk()
        ->assertStructuredContent(function ($json) use ($participant): void {
            $json->where('packing_item.name', 'Water bottle')
                ->where('packing_item.quantity', 2)
                ->where('packing_item.participant_id', $participant->id);
        });

    $item = PackingItem::query()->where('name', 'Water bottle')->firstOrFail();

    TripServer::actingAs($user)
        ->tool(UpdatePackingItemTool::class, [
            'trip_id' => $trip->id,
            'packing_item_id' => $item->id,
            'quantity' => 3,
        ])
        ->assertOk()
        ->assertStructuredContent(function ($json) use ($participant): void {
            $json->where('packing_item.name', 'Water bottle')
                ->where('packing_item.quantity', 3)
                ->where('packing_item.comment', 'Bring a reusable one')
                ->where('packing_item.participant_id', $participant->id);
        });

    TripServer::actingAs($user)
        ->tool(UpdatePackingItemTool::class, [
            'trip_id' => $trip->id,
            'packing_item_id' => $item->id,
            'participant_id' => null,
        ])
        ->assertOk()
        ->assertStructuredContent(fn ($json) => $json->where('packing_item.participant_id', null));

    TripServer::actingAs($user)
        ->tool(DeletePackingItemTool::class, [
            'trip_id' => $trip->id,
            'packing_item_id' => $item->id,
        ])
        ->assertOk()
        ->assertStructuredContent([
            'deleted' => true,
            'packing_item_id' => $item->id,
        ]);

    $this->assertModelMissing($item);
});

test('packing item tools return validation errors for invalid names and quantities', function (): void {
    $user = User::factory()->create();
    $trip = Trip::factory()->create();
    $trip->members()->attach($user, ['role' => 'member']);
    $item = PackingItem::factory()->create(['trip_id' => $trip->id, 'quantity' => 2]);

    TripServer::actingAs($user)
        ->tool(CreatePackingItemTool::class, [
            'trip_id' => $trip->id,
            'name' => '',
            'quantity' => 0,
        ])
        ->assertHasErrors();

    TripServer::actingAs($user)
        ->tool(UpdatePackingItemTool::class, [
            'trip_id' => $trip->id,
            'packing_item_id' => $item->id,
            'name' => '',
            'quantity' => -1,
        ])
        ->assertHasErrors();

    expect($item->fresh()->quantity)->toBe(2);
});

test('cross-trip items and participants return opaque errors', function (): void {
    $user = User::factory()->create();
    $trip = Trip::factory()->create();
    $otherTrip = Trip::factory()->create();
    $trip->members()->attach($user, ['role' => 'member']);
    $otherParticipant = TripParticipant::factory()->create(['trip_id' => $otherTrip->id]);
    $otherItem = PackingItem::factory()->create([
        'trip_id' => $otherTrip->id,
        'name' => 'Private other-trip item',
    ]);

    TripServer::actingAs($user)
        ->tool(CreatePackingItemTool::class, [
            'trip_id' => $trip->id,
            'name' => 'Shared item',
            'participant_id' => $otherParticipant->id,
        ])
        ->assertHasErrors(['Packing item or participant not found.']);

    TripServer::actingAs($user)
        ->tool(UpdatePackingItemTool::class, [
            'trip_id' => $trip->id,
            'packing_item_id' => $otherItem->id,
            'name' => 'Attempted update',
        ])
        ->assertHasErrors(['Packing item or participant not found.'])
        ->assertDontSee('Private other-trip item');

    TripServer::actingAs($user)
        ->tool(DeletePackingItemTool::class, [
            'trip_id' => $trip->id,
            'packing_item_id' => $otherItem->id,
        ])
        ->assertHasErrors(['Packing item or participant not found.']);

    $this->assertModelExists($otherItem);
});

test('a member with an unauthorized role cannot mutate packing items', function (): void {
    $user = User::factory()->create();
    $trip = Trip::factory()->create();
    $trip->members()->attach($user, ['role' => 'guest']);
    $item = PackingItem::factory()->create(['trip_id' => $trip->id]);

    TripServer::actingAs($user)
        ->tool(CreatePackingItemTool::class, [
            'trip_id' => $trip->id,
            'name' => 'Denied item',
        ])
        ->assertHasErrors(['not authorized']);

    TripServer::actingAs($user)
        ->tool(UpdatePackingItemTool::class, [
            'trip_id' => $trip->id,
            'packing_item_id' => $item->id,
            'name' => 'Denied update',
        ])
        ->assertHasErrors(['not authorized']);

    TripServer::actingAs($user)
        ->tool(DeletePackingItemTool::class, [
            'trip_id' => $trip->id,
            'packing_item_id' => $item->id,
        ])
        ->assertHasErrors(['not authorized']);

    expect($item->fresh()->name)->not->toBe('Denied update');
});

test('nonmembers receive the same not-found error for a trip', function (): void {
    $user = User::factory()->create();
    $trip = Trip::factory()->create();

    TripServer::actingAs($user)
        ->tool(CreatePackingItemTool::class, [
            'trip_id' => $trip->id,
            'name' => 'Not allowed',
        ])
        ->assertHasErrors(['Trip not found.']);
});
