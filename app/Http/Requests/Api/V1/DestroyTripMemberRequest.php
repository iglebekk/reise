<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Trip;
use Illuminate\Foundation\Http\FormRequest;

class DestroyTripMemberRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        $trip = $user?->currentTenant;

        if (! $trip instanceof Trip) {
            return false;
        }

        $member = $trip->members()->findOrFail($this->route('user'));

        return $user->can('removeMember', [$trip, $member]);
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
