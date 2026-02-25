<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Industry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCollaborationPostRequest extends FormRequest
{
    public const COLLABORATION_TYPES = [
        'distributor_channel_partner', 'investor_funding', 'strategic_partner', 'joint_venture', 'marketing_partner',
        'vendor_supplier', 'export_import_partner', 'technology_partner', 'co_founder', 'franchise_partner',
        'advisory_mentor', 'hiring_talent', 'other',
    ];

    public const SCOPES = ['same_city', 'same_state', 'same_country', 'international'];
    public const MODELS = ['revenue_share', 'commission_based', 'equity', 'profit_sharing', 'fixed_contract', 'open_for_discussion'];
    public const BUSINESS_STAGES = ['idea_stage', 'early_revenue', 'growing_10l_1cr', 'scaling_1cr_10cr', 'established_10cr_plus'];
    public const YEARS = ['lt_1_year', '1_3_years', '3_7_years', '7_plus'];
    public const URGENCIES = ['immediate_30_days', '1_3_months', '3_6_months', 'exploratory'];

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('scope') !== 'international') {
            $this->merge(['countries_of_interest' => null]);
        }
    }

    public function rules(): array
    {
        return [
            'collaboration_type' => ['required', Rule::in(self::COLLABORATION_TYPES)],
            'title' => ['required', 'string', 'max:80'],
            'description' => ['required', 'string', 'min:500'],
            'scope' => ['required', Rule::in(self::SCOPES)],
            'countries_of_interest' => ['nullable', 'array', 'required_if:scope,international', 'min:1'],
            'countries_of_interest.*' => ['string', 'max:100'],
            'preferred_model' => ['nullable', Rule::in(self::MODELS)],
            'industry_id' => [
                'required',
                'uuid',
                Rule::exists('industries', 'id')->where(fn ($q) => $q->where('is_active', true)),
                function ($attribute, $value, $fail): void {
                    $industry = Industry::query()->select(['id'])->where('id', $value)->first();
                    if (! $industry) {
                        return;
                    }

                    $hasChildren = Industry::query()->where('parent_id', $value)->where('is_active', true)->exists();
                    if ($hasChildren) {
                        $fail('Please select a leaf industry.');
                    }
                },
            ],
            'business_stage' => ['required', Rule::in(self::BUSINESS_STAGES)],
            'years_in_operation' => ['required', Rule::in(self::YEARS)],
            'urgency' => ['required', Rule::in(self::URGENCIES)],
        ];
    }
}
