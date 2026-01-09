<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMemberActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $member = $this->route('member');
        if ($member instanceof User) {
            $this->merge(['member_id' => $member->id]);
        } elseif ($member) {
            $this->merge(['member_id' => $member]);
        }
    }

    public function rules(): array
    {
        $type = $this->route('type');

        $baseRules = [
            'member_id' => ['required', 'uuid', 'exists:users,id'],
        ];

        return match ($type) {
            'p2p-meetings' => $baseRules + [
                'peer_user_id' => ['required', 'uuid', 'exists:users,id'],
                'meeting_date' => ['required', 'date_format:Y-m-d'],
                'meeting_place' => ['required', 'string', 'max:255'],
                'remarks' => ['required', 'string'],
            ],
            'referrals' => $baseRules + [
                'to_user_id' => ['required', 'uuid', 'exists:users,id'],
                'referral_type' => [
                    'required',
                    'string',
                    Rule::in([
                        'customer_referral',
                        'b2b_referral',
                        'b2g_referral',
                        'collaborative_projects',
                        'referral_partnerships',
                        'vendor_referrals',
                        'others',
                    ]),
                ],
                'referral_date' => ['required', 'date_format:Y-m-d'],
                'referral_of' => ['required', 'string'],
                'phone' => ['required', 'string', 'max:30'],
                'email' => ['required', 'email'],
                'address' => ['required', 'string'],
                'hot_value' => ['required', 'integer', 'min:1', 'max:5'],
                'remarks' => ['required', 'string'],
            ],
            'business-deals' => $baseRules + [
                'to_user_id' => ['required', 'uuid', 'exists:users,id'],
                'deal_date' => ['required', 'date_format:Y-m-d'],
                'deal_amount' => ['required', 'numeric', 'min:0'],
                'business_type' => ['required', 'in:new,repeat'],
                'comment' => ['nullable', 'string'],
            ],
            'requirements' => $baseRules + [
                'subject' => ['required', 'string', 'max:255'],
                'description' => ['required', 'string'],
                'media_id' => ['nullable', 'uuid'],
                'region_label' => ['required', 'string', 'max:50'],
                'city_name' => ['required', 'string', 'max:100'],
                'category' => ['required', 'string', 'max:100'],
                'status' => ['nullable', 'in:open,in_progress,closed'],
            ],
            'testimonials' => $baseRules + [
                'to_user_id' => ['required', 'uuid', 'exists:users,id'],
                'content' => ['required', 'string'],
                'media_id' => ['nullable', 'uuid'],
            ],
            default => $baseRules,
        };
    }
}
