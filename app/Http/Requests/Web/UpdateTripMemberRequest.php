<?php

namespace App\Http\Requests\Web;

use App\Models\Trip;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTripMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $trip = $user?->currentTenant;

        if (! $trip instanceof Trip) {
            return false;
        }

        $member = $trip->members()->findOrFail($this->route('user'));
        $role = $this->input('role');

        if (! is_string($role) || ! in_array($role, ['admin', 'member'], true)) {
            return $user->can('invite', $trip);
        }

        return $user->can('updateMemberRole', [$trip, $member, $role]);
    }

    /**
     * @return array<string, array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'role' => ['required', Rule::in(['admin', 'member'])],
        ];
    }
}
