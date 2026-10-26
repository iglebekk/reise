<?php

namespace App\Mcp\Tools;

use App\Actions\SelectCurrentTenant;
use App\Models\User;
use App\Services\SearchSimilarTrips;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Finds similar past trips and safe anonymous inspiration for a trip the authenticated user belongs to.')]
#[IsReadOnly]
class SearchSimilarTripsTool extends Tool
{
    protected string $name = 'search_similar_trips';

    public function shouldRegister(Request $request): bool
    {
        return $request->user() instanceof User;
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return Response::error('Authentication is required to search similar trips.');
        }

        $validated = $request->validate([
            'trip_id' => ['required', 'integer', 'min:1'],
        ]);

        try {
            app(SelectCurrentTenant::class)($user, (int) $validated['trip_id']);
        } catch (ModelNotFoundException) {
            return Response::error('Trip not found.');
        }

        $results = app(SearchSimilarTrips::class)->handle($user);

        return Response::structured(['results' => $results]);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'trip_id' => $schema->integer()->description('The trip ID to use for similar-trip search.')->required(),
        ];
    }
}
