<?php

namespace App\Http\Requests;

use App\Support\PgEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CollaborationStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $businessStage = $this->input('business_stage');

        if ($businessStage === 'growing_101_1cr') {
            $businessStage = 'growing_10l_1cr';
        }

        $countries = $this->input('countries_of_interest');

        if (is_string($countries)) {
            $countries = array_values(array_filter(array_map('trim', explode(',', $countries))));
        }

        $this->merge([
            'business_stage' => $businessStage,
            'countries_of_interest' => $countries,
        ]);
    }

    public function rules(): array
    {
        return [
            'collaboration_type_id' => ['required', 'uuid', 'exists:collaboration_types,id'],
            'title' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string'],
            'scope' => ['required', Rule::in(PgEnum::values('collaboration_scope_enum'))],
            'countries_of_interest' => ['nullable', 'array'],
            'countries_of_interest.*' => ['string', 'size:2'],
            'preferred_model' => ['required', Rule::in(PgEnum::values('collaboration_model_enum'))],
            'industry_id' => ['required', 'uuid', 'exists:industries,id'],
            'business_stage' => ['required', Rule::in(PgEnum::values('business_stage_enum'))],
            'years_in_operation' => ['required', Rule::in(PgEnum::values('years_in_operation_enum'))],
            'urgency' => ['required', Rule::in(PgEnum::values('urgency_enum'))],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($v): void {
            if ($this->input('scope') === 'international') {
                $countries = $this->input('countries_of_interest');

                if (!is_array($countries) || count($countries) === 0) {
                    $v->errors()->add('countries_of_interest', 'countries_of_interest is required when scope is international.');
                }
            }
        });
    }
}
