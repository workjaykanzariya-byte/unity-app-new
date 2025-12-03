<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['sometimes', 'required', 'string', 'max:100'],
            'last_name' => ['sometimes', 'nullable', 'string', 'max:100'],
            'company_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'designation' => ['sometimes', 'nullable', 'string', 'max:255'],
            'about' => ['sometimes', 'nullable', 'string'],
            'gender' => ['sometimes', 'nullable', 'string', 'in:male,female,other'],
            'dob' => ['sometimes', 'nullable', 'date'],
            'experience_years' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'experience_summary' => ['sometimes', 'nullable', 'string'],
            'city_id' => ['sometimes', 'nullable', 'uuid', 'exists:cities,id'],
            'skills' => ['sometimes', 'nullable', 'array'],
            'skills.*' => ['string', 'max:150'],
            'interests' => ['sometimes', 'nullable', 'array'],
            'interests.*' => ['string', 'max:150'],
            'social_links' => ['sometimes', 'nullable', 'array'],
            'social_links.linkedin' => ['sometimes', 'nullable', 'url'],
            'social_links.facebook' => ['sometimes', 'nullable', 'url'],
            'social_links.instagram' => ['sometimes', 'nullable', 'url'],
            'social_links.website' => ['sometimes', 'nullable', 'url'],
            'profile_photo_id' => ['sometimes', 'nullable', 'uuid'],
            'cover_photo_id' => ['sometimes', 'nullable', 'uuid'],
        ];
    }
}
