<?php

namespace App\Mcp\Tools;

use App\Actions\DeletePackingItem;
use App\Actions\SelectCurrentTenant;
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
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[Description('Deletes a packing item from a trip list.')]
#[IsDestructive]
class DeletePackingItemTool extends Tool
{
    protected string $name = 'delete_packing_item';

    public function shouldRegister(Request $request): bool
    {
        return $request->user() instanceof User;
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return Response::error('Authentication is required to delete a packing item.');
        }

        $validated = $request->validate([
            'trip_id' => ['required', 'integer', 'min:1'],
            'packing_item_id' => ['required', 'integer', 'min:1'],
        ]);

        try {
            $trip = app(SelectCurrentTenant::class)($user, (int) $validated['trip_id']);
        } catch (ModelNotFoundException) {
            return Response::error('Trip not found.');
        }

        try {
            app(DeletePackingItem::class)($user, $trip, (int) $validated['packing_item_id']);
        } catch (AuthorizationException) {
            return Response::error('You are not authorized to modify this trip.');
        } catch (ModelNotFoundException) {
            return Response::error('Packing item or participant not found.');
        }

        return Response::structured([
            'deleted' => true,
            'packing_item_id' => (int) $validated['packing_item_id'],
        ]);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'trip_id' => $schema->integer()->min(1)->description('The trip ID.')->required(),
            'packing_item_id' => $schema->integer()->min(1)->description('The packing item ID.')->required(),
        ];
    }
}
