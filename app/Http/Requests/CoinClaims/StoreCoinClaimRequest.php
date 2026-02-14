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

    protected function prepareForValidation(): void
    {
        $files = $this->input('files', []);

        foreach (['payment_proof_file', 'event_confirmation_file', 'membership_confirmation_file'] as $fileKey) {
            if ($this->hasFile($fileKey)) {
                $files[$fileKey] = $this->file($fileKey);
                continue;
            }

            if ($this->hasFile('fields.'.$fileKey)) {
                $files[$fileKey] = $this->file('fields.'.$fileKey);
            }
        }

        $this->merge([
            'files' => is_array($files) ? $files : [],
        ]);
    }

    public function rules(): array
    {
        $codes = array_column(CoinClaimActivityRegistry::all(), 'code');
        $activityCode = (string) $this->input('activity_code');

        $rules = [
            'activity_code' => ['required', 'string', Rule::in($codes)],
            'fields' => ['required', 'array'],
            'files' => ['sometimes', 'array'],
        ];

        return array_merge($rules, $this->activitySpecificRules($activityCode));
    }

    private function activitySpecificRules(string $activityCode): array
    {
        return match ($activityCode) {
            'renew_membership' => [
                'fields.renewal_date' => ['required', 'date_format:Y-m-d'],
                'files.payment_proof_file' => ['required', 'file', 'max:20480', 'mimes:jpg,jpeg,png,pdf'],
            ],
            'invite_visitor' => [
                'fields.visitor_name' => ['required', 'string', 'max:255'],
                'fields.visitor_mobile' => ['required', 'regex:/^\+?[0-9]{8,15}$/'],
                'fields.visitor_email' => ['required', 'email'],
                'fields.visit_date' => ['required', 'date_format:Y-m-d'],
                'files.event_confirmation_file' => ['nullable', 'file', 'max:20480', 'mimes:jpg,jpeg,png,pdf'],
            ],
            'new_member_addition' => [
                'fields.new_member_name' => ['required', 'string', 'max:255'],
                'fields.new_member_mobile' => ['required', 'regex:/^\+?[0-9]{8,15}$/'],
                'fields.new_member_email' => ['required', 'email'],
                'fields.joining_date' => ['required', 'date_format:Y-m-d'],
                'files.membership_confirmation_file' => ['nullable', 'file', 'max:20480', 'mimes:jpg,jpeg,png,pdf'],
            ],
            default => CoinClaimActivityRegistry::rulesFor($activityCode),
        };
    }

    public function messages(): array
    {
        return [
            'fields.required' => 'The fields payload is required.',
            'files.*.max' => 'Each file must be <= 20MB.',
        ];
    }
}
