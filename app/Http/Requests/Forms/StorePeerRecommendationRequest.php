<?php

namespace App\Http\Requests\Forms;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePeerRecommendationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'peer_name' => ['required', 'string', 'max:150'],
            'peer_mobile' => ['nullable', 'string', 'max:50'],
            'peer_email' => ['nullable', 'email', 'max:190'],
            'peer_city' => ['nullable', 'string', 'max:120'],
            'peer_business' => ['nullable', 'string', 'max:150'],
            'how_well_known' => ['required', Rule::in(['close_friend', 'business_associate', 'client', 'community_contact'])],
            'is_aware' => ['required', 'boolean'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
