<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Trip;
use Illuminate\Foundation\Http\FormRequest;

class DestroyPackingItemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
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
        return [
        ];
    }
}
