<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Trip;
use Illuminate\Foundation\Http\FormRequest;

class DestroyTripInvitationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        $trip = $user?->currentTenant;

        return $trip instanceof Trip && $user->can('invite', $trip);
    }

    /**
     * @return array<string, array<mixed>|string>
     */
    public function rules(): array
    {
        return [
        ];
    }
}
