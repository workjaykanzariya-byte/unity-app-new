<?php

namespace App\Http\Requests\Api\V1\Profile;

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
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'designation' => ['nullable', 'string', 'max:255'],
            'about' => ['nullable', 'string'],
            'gender' => ['nullable', 'in:male,female,other'],
            'dob' => ['nullable', 'date'],
            'experience_years' => ['nullable', 'integer', 'min:0', 'max:80'],
            'experience_summary' => ['nullable', 'string'],
            'city_id' => ['nullable', 'integer'],
            'city' => ['nullable', 'string', 'max:255'],
            'skills' => ['nullable', 'array'],
            'skills.*' => ['string', 'max:100'],
            'interests' => ['nullable', 'array'],
            'interests.*' => ['string', 'max:100'],
            'social_links' => ['nullable', 'array'],
            'social_links.linkedin' => ['nullable', 'url'],
            'social_links.facebook' => ['nullable', 'url'],
            'social_links.instagram' => ['nullable', 'url'],
            'social_links.website' => ['nullable', 'url'],
            'profile_photo_id' => ['nullable'],
            'cover_photo_id' => ['nullable'],
        ];
    }
}
