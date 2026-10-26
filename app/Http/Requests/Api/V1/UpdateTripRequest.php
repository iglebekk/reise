<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Trip;
use App\Services\InspirationCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateTripRequest extends FormRequest
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
            'destination' => ['sometimes', 'required', 'string', 'max:255'],
            'start_date' => ['sometimes', 'required', 'date_format:Y-m-d'],
            'end_date' => ['sometimes', 'required', 'date_format:Y-m-d'],
            'type' => ['sometimes', 'required', 'string', Rule::in(InspirationCatalog::tripTypes())],
            'transport' => ['sometimes', 'required', 'string', Rule::in(InspirationCatalog::transportOptions())],
            'accommodation' => ['sometimes', 'required', 'string', Rule::in(InspirationCatalog::accommodationOptions())],
            'activities' => ['sometimes', 'required', 'array'],
            'activities.*' => ['required', 'string', Rule::in(InspirationCatalog::activityOptions())],
            'description' => ['sometimes', 'nullable', 'string', 'max:10000'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->has('start_date') || $validator->errors()->has('end_date')) {
                return;
            }

            $trip = $this->user()?->currentTenant;

            if (! $trip instanceof Trip) {
                return;
            }

            $startDate = $this->input('start_date', $trip->start_date->toDateString());
            $endDate = $this->input('end_date', $trip->end_date->toDateString());

            if (is_string($startDate) && is_string($endDate) && $endDate < $startDate) {
                $validator->errors()->add('end_date', 'The end date must be after or equal to the start date.');
            }
        }];
    }
}
