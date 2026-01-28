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
        ];
    }
}
