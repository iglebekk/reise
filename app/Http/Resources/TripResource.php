<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TripResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'destination' => $this->destination,
            'start_date' => $this->start_date->toDateString(),
            'end_date' => $this->end_date->toDateString(),
            'duration_days' => $this->durationInDays(),
            'type' => $this->type,
            'transport' => $this->transport,
            'accommodation' => $this->accommodation,
            'activities' => $this->activities,
            'description' => $this->description,
        ];
    }
}
