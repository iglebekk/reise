<?php

namespace App\Http\Requests\Web;

use App\Models\Trip;
use Illuminate\Foundation\Http\FormRequest;

class DestroyTripMemberRequest extends FormRequest
{
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
        return [];
    }
}
