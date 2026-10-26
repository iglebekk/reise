<?php

namespace App\Http\Requests\Web;

use App\Models\Trip;
use App\Services\InspirationCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTripRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $trip = $user?->currentTenant;

        return $trip instanceof Trip && $user->can('update', $trip);
    }

    protected function prepareForValidation(): void
    {
        if ($this->boolean('metadata_form') && ! $this->exists('activities')) {
            $this->merge(['activities' => []]);
        }
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
            'activities' => ['sometimes', 'array'],
            'activities.*' => ['required', 'string', Rule::in(InspirationCatalog::activityOptions())],
            'description' => ['sometimes', 'nullable', 'string', 'max:10000'],
        ];
    }
}
