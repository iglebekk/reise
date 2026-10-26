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

#[Description('Shows details for one trip the authenticated user belongs to.')]
#[IsReadOnly]
class ShowTripTool extends Tool
{
    protected string $name = 'show_trip';

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
            return Response::error('Authentication is required to show a trip.');
        }

        $validated = $request->validate(['trip_id' => ['required', 'integer', 'min:1']]);

        try {
            $trip = app(SelectCurrentTenant::class)($user, (int) $validated['trip_id']);
        } catch (ModelNotFoundException) {
            return Response::error('Trip not found.');
        }

        return Response::structured(['trip' => [
            'id' => $trip->id,
            'destination' => $trip->destination,
            'start_date' => $trip->start_date->toDateString(),
            'end_date' => $trip->end_date->toDateString(),
            'duration_days' => $trip->durationInDays(),
            'type' => $trip->type,
            'transport' => $trip->transport,
            'accommodation' => $trip->accommodation,
            'activities' => $trip->activities,
            'description' => $trip->description,
        ]]);

    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'trip_id' => $schema->integer()->description('The trip ID to show.')->required(),
        ];
    }
}
