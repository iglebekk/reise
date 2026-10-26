<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SimilarTripResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $result = $this->resource;

        if (($result['source'] ?? null) === 'anonymous') {
            return [
                'source' => 'anonymous',
                'categories' => array_intersect_key($result['categories'] ?? [], array_flip([
                    'type',
                    'transport',
                    'accommodation',
                    'duration_group',
                ])),
                'item_names' => array_values(array_filter($result['item_names'] ?? [], 'is_string')),
            ];
        }

        $trip = $result['trip'] ?? [];

        return [
            'source' => 'own',
            'trip' => [
                'id' => $trip['id'] ?? null,
                'destination' => $trip['destination'] ?? null,
                'start_date' => $trip['start_date'] ?? null,
                'end_date' => $trip['end_date'] ?? null,
                'duration_days' => $trip['duration_days'] ?? null,
                'type' => $trip['type'] ?? null,
                'transport' => $trip['transport'] ?? null,
                'accommodation' => $trip['accommodation'] ?? null,
                'activities' => $trip['activities'] ?? [],
                'description' => $trip['description'] ?? null,
                'participants' => array_map(fn (array $participant): array => [
                    'id' => $participant['id'] ?? null,
                    'name' => $participant['name'] ?? null,
                    'age' => $participant['age'] ?? null,
                    'user_id' => $participant['user_id'] ?? null,
                    'packing_items' => array_map(fn (array $item): array => $this->ownPackingItem($item), $participant['packing_items'] ?? []),
                ], $trip['participants'] ?? []),
                'packing_items' => array_map(fn (array $item): array => $this->ownPackingItem($item), $trip['packing_items'] ?? []),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    private function ownPackingItem(array $item): array
    {
        return [
            'id' => $item['id'] ?? null,
            'participant_id' => $item['participant_id'] ?? null,
            'name' => $item['name'] ?? null,
            'quantity' => $item['quantity'] ?? null,
            'comment' => $item['comment'] ?? null,
        ];
    }
}
