<?php

namespace App\Http\Requests\Web;

use App\Models\Trip;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTripParticipantRequest extends FormRequest
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
        $trip = $this->user()?->currentTenant;

        return [
            'name' => ['required', 'string', 'max:255'],
            'age' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:120'],
            'user_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('trip_user', 'user_id')->where('trip_id', $trip?->getKey()),
            ],
        ];
    }
}
