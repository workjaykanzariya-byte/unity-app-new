<?php

namespace App\Http\Requests\Activity;

use App\Enums\ReferralType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReferralRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $normalized = ReferralType::fromInput($this->input('referral_type'));

        if ($normalized) {
            $this->merge(['referral_type' => $normalized->value]);
        }
    }

    public function rules(): array
    {
        return [
            'to_user_id' => ['required', 'uuid', 'exists:users,id'],
            'referral_type' => [
                'required',
                'string',
                Rule::in(ReferralType::values()),
            ],
            'referral_date' => ['required', 'date_format:Y-m-d'],
            'referral_of' => ['required', 'string'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['required', 'email'],
            'address' => ['required', 'string'],
            'hot_value' => ['required', 'integer', 'min:1', 'max:5'],
            'remarks' => ['required', 'string'],
        ];
    }
}
