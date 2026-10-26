<?php

namespace App\Http\Requests\Api\V1;

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
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'age' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:120'],
            'user_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('trip_user', 'user_id')->where('trip_id', $this->user()->current_tenant_id),
            ],
        ];
    }
}
