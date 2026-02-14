<?php

namespace App\Http\Requests\CoinClaims;

use App\Support\CoinClaims\CoinClaimActivityRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCoinClaimRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $codes = array_column(CoinClaimActivityRegistry::all(), 'code');
        $activityCode = (string) $this->input('activity_code');

        return array_merge([
            'activity_code' => ['required', 'string', Rule::in($codes)],
            'fields' => ['required', 'array'],
            'files' => ['nullable', 'array'],
        ], CoinClaimActivityRegistry::rulesFor($activityCode));
    }

    public function messages(): array
    {
        return [
            'fields.required' => 'The fields payload is required.',
            'files.*.max' => 'Each file must be <= 20MB.',
        ];
    }
}
