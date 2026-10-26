<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Trip;
use App\Services\InspirationCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTripRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', Trip::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'destination' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'type' => ['required', 'string', Rule::in(InspirationCatalog::tripTypes())],
            'transport' => ['required', 'string', Rule::in(InspirationCatalog::transportOptions())],
            'accommodation' => ['required', 'string', Rule::in(InspirationCatalog::accommodationOptions())],
            'activities' => ['sometimes', 'nullable', 'array'],
            'activities.*' => ['string', Rule::in(InspirationCatalog::activityOptions())],
            'description' => ['nullable', 'string', 'max:10000'],
        ];
    }
}
