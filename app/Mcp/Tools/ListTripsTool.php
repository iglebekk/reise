<?php

namespace App\Mcp\Tools;

use App\Models\User;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Lists trips the authenticated user belongs to.')]
#[IsReadOnly]
class ListTripsTool extends Tool
{
    protected string $name = 'list_trips';

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
            return Response::error('Authentication is required to list trips.');
        }

        $trips = $user->trips()->get()->map(fn ($trip): array => [
            'id' => $trip->id,
            'destination' => $trip->destination,
            'start_date' => $trip->start_date->toDateString(),
            'end_date' => $trip->end_date->toDateString(),
            'duration_days' => $trip->durationInDays(),
        ])->all();

        return Response::structured(['trips' => $trips]);
    }
}
