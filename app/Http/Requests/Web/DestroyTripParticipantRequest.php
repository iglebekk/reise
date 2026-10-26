<?php

namespace App\Http\Requests\Web;

use App\Models\Trip;
use Illuminate\Foundation\Http\FormRequest;

class DestroyTripParticipantRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $trip = $user?->currentTenant;

        return $trip instanceof Trip && $user->can('update', $trip);
    }

    /**
     * @return array<string, array<mixed>|string>
     */
    public function rules(): array
    {
        return [];
    }
}
