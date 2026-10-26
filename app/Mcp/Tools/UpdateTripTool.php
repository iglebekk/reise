<?php

namespace App\Mcp\Tools;

use App\Actions\SelectCurrentTenant;
use App\Actions\UpdateTrip;
use App\Models\Trip;
use App\Models\User;
use App\Services\InspirationCatalog;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Updates fields on a trip the authenticated user belongs to.')]
class UpdateTripTool extends Tool
{
    protected string $name = 'update_trip';

    public function shouldRegister(Request $request): bool
    {
        return $request->user() instanceof User;
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return Response::error('Authentication is required to update a trip.');
        }

        $validated = $request->validate([
            'trip_id' => ['required', 'integer', 'min:1'],
            'destination' => ['sometimes', 'required', 'string', 'max:255'],
            'start_date' => ['sometimes', 'required', 'date_format:Y-m-d'],
            'end_date' => ['sometimes', 'required', 'date_format:Y-m-d'],
            'type' => ['sometimes', 'required', 'string', Rule::in(InspirationCatalog::tripTypes())],
            'transport' => ['sometimes', 'required', 'string', Rule::in(InspirationCatalog::transportOptions())],
            'accommodation' => ['sometimes', 'required', 'string', Rule::in(InspirationCatalog::accommodationOptions())],
            'activities' => ['sometimes', 'required', 'array'],
            'activities.*' => ['required', 'string', Rule::in(InspirationCatalog::activityOptions())],
            'description' => ['sometimes', 'nullable', 'string', 'max:10000'],
        ]);

        try {
            $trip = app(SelectCurrentTenant::class)($user, (int) $validated['trip_id']);
        } catch (ModelNotFoundException) {
            return Response::error('Trip not found.');
        }

        $attributes = array_diff_key($validated, ['trip_id' => true]);

        if ($attributes === []) {
            return Response::error('At least one trip field must be provided.');
        }

        $startDate = $attributes['start_date'] ?? $trip->start_date->toDateString();
        $endDate = $attributes['end_date'] ?? $trip->end_date->toDateString();

        Validator::make([
            'start_date' => $startDate,
            'end_date' => $endDate,
        ], [
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
        ])->validate();

        $updatedTrip = app(UpdateTrip::class)($user, $trip, $attributes);

        return Response::structured(['trip' => $this->tripMetadata($updatedTrip)]);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'trip_id' => $schema->integer()->description('The ID of the trip to update.')->required(),
            'destination' => $schema->string()->max(255)->description('The trip destination.'),
            'start_date' => $schema->string()->format('date')->description('Trip start date in YYYY-MM-DD format.'),
            'end_date' => $schema->string()->format('date')->description('Trip end date in YYYY-MM-DD format.'),
            'type' => $schema->string()->enum(InspirationCatalog::tripTypes()),
            'transport' => $schema->string()->enum(InspirationCatalog::transportOptions()),
            'accommodation' => $schema->string()->enum(InspirationCatalog::accommodationOptions()),
            'activities' => $schema->array()->items($schema->string()->enum(InspirationCatalog::activityOptions())),
            'description' => $schema->string()->nullable()->max(10000),
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
