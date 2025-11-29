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
        $userId = $this->user()?->id;

        return [
            'first_name' => 'sometimes|required|string|max:100',
            'last_name' => 'sometimes|nullable|string|max:100',
            'display_name' => 'sometimes|nullable|string|max:150',
            'designation' => 'sometimes|nullable|string|max:100',
            'company_name' => 'sometimes|nullable|string|max:150',
            'profile_photo_url' => 'sometimes|nullable|url|max:2000',
            'short_bio' => 'sometimes|nullable|string',
            'long_bio_html' => 'sometimes|nullable|string',
            'business_type' => 'sometimes|nullable|string|max:100',
            'turnover_range' => 'sometimes|nullable|string|max:100',
            'city_id' => 'sometimes|nullable|uuid|exists:cities,id',
            'email' => 'sometimes|required|email:rfc,dns|max:255|unique:users,email,' . $userId,
            'phone' => 'sometimes|nullable|string|max:30|unique:users,phone,' . $userId,
            'industry_tags' => 'sometimes|array',
            'industry_tags.*' => 'string|max:150',
            'target_regions' => 'sometimes|array',
            'target_regions.*' => 'string|max:150',
            'target_business_categories' => 'sometimes|array',
            'target_business_categories.*' => 'string|max:150',
            'hobbies_interests' => 'sometimes|array',
            'hobbies_interests.*' => 'string|max:150',
            'leadership_roles' => 'sometimes|array',
            'leadership_roles.*' => 'string|max:150',
            'special_recognitions' => 'sometimes|array',
            'special_recognitions.*' => 'string|max:150',
        ];
    }
}
