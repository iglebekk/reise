<?php

namespace App\Http\Requests\Web;

use App\Models\Trip;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePackingItemRequest extends FormRequest
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
            'quantity' => ['sometimes', 'required', 'integer', 'min:1'],
            'comment' => ['sometimes', 'nullable', 'string', 'max:10000'],
            'participant_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('trip_participants', 'id')->where('trip_id', $trip?->getKey()),
            ],
        ];
    }
}
