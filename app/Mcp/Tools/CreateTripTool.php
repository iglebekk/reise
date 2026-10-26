<?php

namespace App\Mcp\Tools;

use App\Actions\CreateTripWithAdmin;
use App\Models\Trip;
use App\Models\User;
use App\Services\InspirationCatalog;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Creates a trip for the authenticated user, who becomes its admin.')]
class CreateTripTool extends Tool
{
    protected string $name = 'create_trip';

    public function shouldRegister(Request $request): bool
    {
        return $request->user() instanceof User;
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return Response::error('Authentication is required to create a trip.');
        }

        $attributes = $request->validate([
            'destination' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'type' => ['required', 'string', Rule::in(InspirationCatalog::tripTypes())],
            'transport' => ['required', 'string', Rule::in(InspirationCatalog::transportOptions())],
            'accommodation' => ['required', 'string', Rule::in(InspirationCatalog::accommodationOptions())],
            'activities' => ['sometimes', 'nullable', 'array'],
            'activities.*' => ['required', 'string', Rule::in(InspirationCatalog::activityOptions())],
            'description' => ['sometimes', 'nullable', 'string', 'max:10000'],
        ]);

        $trip = app(CreateTripWithAdmin::class)($user, $attributes);

        return Response::structured(['trip' => $this->tripMetadata($trip)]);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'destination' => $schema->string()->max(255)->description('The trip destination.')->required(),
            'start_date' => $schema->string()->format('date')->description('The trip start date in YYYY-MM-DD format.')->required(),
            'end_date' => $schema->string()->format('date')->description('The trip end date in YYYY-MM-DD format.')->required(),
            'type' => $schema->string()->enum(InspirationCatalog::tripTypes())->required(),
            'transport' => $schema->string()->enum(InspirationCatalog::transportOptions())->required(),
            'accommodation' => $schema->string()->enum(InspirationCatalog::accommodationOptions())->required(),
            'activities' => $schema->array()
                ->items($schema->string()->enum(InspirationCatalog::activityOptions()))
                ->description('Optional trip activities.'),
            'description' => $schema->string()->nullable()->max(10000)->description('Optional trip notes.'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function tripMetadata(Trip $trip): array
    {
        return [
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
        ];
    }
}
