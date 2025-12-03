<?php

namespace App\Http\Requests\Activity;

use Illuminate\Foundation\Http\FormRequest;

class StoreReferralRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'to_user_id' => ['required', 'uuid', 'exists:users,id'],
            'referral_type' => ['required', 'in:customer_referral,b2b_referral,b2c_referral,collaborative_projects,vendor_partnerships,overdue_referrals,others'],
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
