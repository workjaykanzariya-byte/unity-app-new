<?php

namespace App\Http\Requests\Forms;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVisitorRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'event_type' => ['required', Rule::in(['physical', 'virtual'])],
            'event_name' => ['required', 'string', 'max:190'],
            'event_date' => ['required', 'date'],
            'visitor_full_name' => ['required', 'string', 'max:150'],
            'visitor_mobile' => ['required', 'string', 'max:30'],
            'visitor_email' => ['nullable', 'email', 'max:190'],
            'visitor_city' => ['required', 'string', 'max:120'],
            'visitor_business' => ['required', 'string', 'max:150'],
            'how_known' => ['required', Rule::in(['friend', 'business_associate', 'client', 'family', 'community_contact'])],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
