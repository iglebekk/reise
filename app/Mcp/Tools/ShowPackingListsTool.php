<?php

namespace App\Mcp\Tools;

use App\Actions\SelectCurrentTenant;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Shows shared and participant packing lists for one trip the authenticated user belongs to.')]
#[IsReadOnly]
class ShowPackingListsTool extends Tool
{
    protected string $name = 'show_packing_lists';

    public function shouldRegister(Request $request): bool
    {
        return $request->user() instanceof User;
    }

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response|ResponseFactory
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return Response::error('Authentication is required to show packing lists.');
        }

        $validated = $request->validate(['trip_id' => ['required', 'integer', 'min:1']]);

        try {
            $trip = app(SelectCurrentTenant::class)($user, (int) $validated['trip_id']);
        } catch (ModelNotFoundException) {
            return Response::error('Trip not found.');
        }

        $items = $user->currentTenant->packingItems()->get();
        $participants = $user->currentTenant->participants()->get(['id', 'name']);

        return Response::structured([
            'trip_id' => $trip->id,
            'shared_items' => $items->whereNull('participant_id')->map(fn ($item): array => [
                'id' => $item->id,
                'name' => $item->name,
                'quantity' => $item->quantity,
                'comment' => $item->comment,
            ])->values()->all(),
            'participant_lists' => $participants->map(fn ($participant): array => [
                'participant_id' => $participant->id,
                'participant_name' => $participant->name,
                'items' => $items->where('participant_id', $participant->id)->map(fn ($item): array => [
                    'id' => $item->id,
                    'name' => $item->name,
                    'quantity' => $item->quantity,
                    'comment' => $item->comment,
                ])->values()->all(),
            ])->all(),
        ]);

    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'trip_id' => $schema->integer()->description('The trip ID whose packing lists to show.')->required(),
        ];
    }
}
