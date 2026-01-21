<?php

namespace App\Http\Requests\Admin\MembershipPlans;

use App\Models\MembershipPlan;
use App\Support\AdminAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMembershipPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return AdminAccess::isGlobalAdmin($this->user('admin'));
    }

    public function rules(): array
    {
        $planId = $this->route('plan');
        if ($planId instanceof MembershipPlan) {
            $planId = $planId->id;
        }

        return [
            'name' => ['required', 'string', 'max:200'],
            'slug' => ['required', 'string', 'max:200', Rule::unique('membership_plans', 'slug')->ignore($planId)],
            'price' => ['required', 'numeric', 'min:0'],
            'gst_percent' => ['required', 'numeric', 'min:0'],
            'duration_days' => ['required', 'integer', 'min:0'],
            'duration_months' => ['nullable', 'integer', 'min:0'],
            'coins' => ['nullable', 'integer', 'min:0'],
            'is_free' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_free' => $this->boolean('is_free'),
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($this->boolean('is_free') && (float) $this->input('price', 0) > 0) {
                $validator->errors()->add('price', 'Free plans must have a base price of 0.');
            }
        });
    }
}
