<?php

namespace App\Http\Requests\Forms;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeaderInterestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'applying_for' => ['required', Rule::in(['myself', 'referring_friend'])],
            'referred_name' => ['nullable', 'string', 'min:2', 'max:150', 'required_if:applying_for,referring_friend'],
            'referred_mobile' => ['nullable', 'string', 'min:6', 'max:30', 'required_if:applying_for,referring_friend'],
            'leadership_roles' => ['nullable', 'array', 'min:1', 'required_if:applying_for,myself'],
            'leadership_roles.*' => ['string', Rule::in(['DED', 'ID', 'CF', 'CD', 'CVC', 'RSL', 'GLT'])],
            'contribute_city' => ['nullable', 'string', 'max:120', 'required_if:applying_for,myself'],
            'primary_domain' => ['nullable', 'string', 'max:120', 'required_if:applying_for,myself'],
            'why_interested' => ['nullable', 'string', 'max:400', 'required_if:applying_for,myself'],
            'excitement' => ['nullable', Rule::in(['building_people', 'business_collaboration', 'leadership_growth', 'visibility_recognition']), 'required_if:applying_for,myself'],
            'ownership' => ['nullable', Rule::in(['yes', 'yes_with_guidance', 'not_sure_yet']), 'required_if:applying_for,myself'],
            'time_commitment' => ['nullable', Rule::in(['4_6_hours', '6_10_hours', '10_plus_hours']), 'required_if:applying_for,myself'],
            'has_led_before' => ['nullable', 'boolean', 'required_if:applying_for,myself'],
            'message' => ['nullable', 'string'],
        ];
    }
}
