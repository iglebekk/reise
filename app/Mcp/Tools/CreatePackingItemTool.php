<?php

namespace App\Mcp\Tools;

use App\Actions\CreatePackingItem;
use App\Actions\SelectCurrentTenant;
use App\Models\PackingItem;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Adds a packing item to a trip shared or participant list.')]
class CreatePackingItemTool extends Tool
{
    protected string $name = 'create_packing_item';

    public function shouldRegister(Request $request): bool
    {
        return $request->user() instanceof User;
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return Response::error('Authentication is required to create a packing item.');
        }

        $validated = $request->validate([
            'trip_id' => ['required', 'integer', 'min:1'],
            'name' => ['required', 'string', 'max:255'],
            'quantity' => ['sometimes', 'required', 'integer', 'min:1'],
            'comment' => ['sometimes', 'nullable', 'string'],
            'participant_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
        ]);

        try {
            $trip = app(SelectCurrentTenant::class)($user, (int) $validated['trip_id']);
        } catch (ModelNotFoundException) {
            return Response::error('Trip not found.');
        }

        try {
            $item = app(CreatePackingItem::class)($user, $trip, array_diff_key($validated, ['trip_id' => true]));
        } catch (AuthorizationException) {
            return Response::error('You are not authorized to modify this trip.');
        } catch (ModelNotFoundException) {
            return Response::error('Packing item or participant not found.');
        }

        return Response::structured(['packing_item' => $this->itemMetadata($item)]);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'trip_id' => $schema->integer()->min(1)->description('The trip ID.')->required(),
            'name' => $schema->string()->max(255)->description('The packing item name.')->required(),
            'quantity' => $schema->integer()->min(1)->description('The item quantity.'),
            'comment' => $schema->string()->nullable()->description('An optional item note.'),
            'participant_id' => $schema->integer()->min(1)->nullable()->description('The participant ID, or null for a shared item.'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function itemMetadata(PackingItem $item): array
    {
        return [
            'id' => $item->id,
            'participant_id' => $item->participant_id,
            'name' => $item->name,
            'quantity' => $item->quantity,
            'comment' => $item->comment,
        ];
    }
}
