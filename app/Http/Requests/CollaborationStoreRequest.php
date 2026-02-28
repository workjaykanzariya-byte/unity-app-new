<?php

namespace App\Http\Requests;

use App\Support\PgEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CollaborationStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // auth is handled by routes/middleware already
    }

    protected function prepareForValidation(): void
    {
        // Normalize common wrong values coming from app/postman
        $businessStage = $this->input('business_stage');

        // Common typo: growing_101_1cr -> growing_10l_1cr
        if ($businessStage === 'growing_101_1cr') {
            $businessStage = 'growing_10l_1cr';
        }

        // Ensure countries_of_interest is an array if present
        $countries = $this->input('countries_of_interest');
        if (is_string($countries)) {
            // If someone sends "AE,IN" make it ["AE","IN"]
            $countries = array_values(array_filter(array_map('trim', explode(',', $countries))));
        }

        $this->merge([
            'business_stage' => $businessStage,
            'countries_of_interest' => $countries,
        ]);
    }

    public function rules(): array
    {
        $scopeEnum = PgEnum::values('collaboration_scope_enum');
        $modelEnum = PgEnum::values('collaboration_model_enum');
        $stageEnum = PgEnum::values('business_stage_enum');
        $yearsEnum = PgEnum::values('years_in_operation_enum');
        $urgencyEnum = PgEnum::values('urgency_enum');

        return [
            'collaboration_type_id' => ['required', 'uuid', 'exists:collaboration_types,id'],
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:5000'],

            'scope' => ['required', Rule::in($scopeEnum)],
            'countries_of_interest' => ['nullable', 'array'],
            'countries_of_interest.*' => ['string', 'size:2'], // e.g. "AE"

            'preferred_model' => ['required', Rule::in($modelEnum)],
            'industry_id' => ['required', 'uuid', 'exists:industries,id'],

            'business_stage' => ['required', Rule::in($stageEnum)],
            'years_in_operation' => ['required', Rule::in($yearsEnum)],
            'urgency' => ['required', Rule::in($urgencyEnum)],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($v) {
            if ($this->input('scope') === 'international') {
                $countries = $this->input('countries_of_interest');
                if (!is_array($countries) || count($countries) === 0) {
                    $v->errors()->add('countries_of_interest', 'countries_of_interest is required when scope is international.');
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'scope.in' => 'Invalid scope value for collaboration_scope_enum.',
            'preferred_model.in' => 'Invalid preferred_model value for collaboration_model_enum.',
            'business_stage.in' => 'Invalid business_stage value for business_stage_enum.',
            'years_in_operation.in' => 'Invalid years_in_operation value for years_in_operation_enum.',
            'urgency.in' => 'Invalid urgency value for urgency_enum.',
        ];
    }
}
