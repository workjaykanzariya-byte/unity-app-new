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
            'first_name'          => ['sometimes', 'string', 'max:100'],
            'last_name'           => ['sometimes', 'nullable', 'string', 'max:100'],
            'company_name'        => ['sometimes', 'nullable', 'string', 'max:255'],
            'designation'         => ['sometimes', 'nullable', 'string', 'max:255'],

            'about'               => ['sometimes', 'nullable', 'string', 'max:1000'],

            'gender'              => ['sometimes', 'nullable', 'string', 'max:20'],
            'dob'                 => ['sometimes', 'nullable', 'date'],

            'experience_years'    => ['sometimes', 'nullable', 'integer', 'min:0', 'max:80'],
            'experience_summary'  => ['sometimes', 'nullable', 'string'],

            'city'                => ['sometimes', 'nullable', 'string', 'max:150'],
            'city_id'             => ['sometimes', 'nullable', 'uuid'],

            'skills'              => ['sometimes', 'array'],
            'skills.*'            => ['nullable', 'string', 'max:100'],

            'interests'           => ['sometimes', 'array'],
            'interests.*'         => ['nullable', 'string', 'max:100'],

            'social_links'        => ['sometimes', 'array'],
            'social_links.linkedin'  => ['nullable', 'url'],
            'social_links.facebook'  => ['nullable', 'url'],
            'social_links.instagram' => ['nullable', 'url'],
            'social_links.website'   => ['nullable', 'url'],

            'profile_photo_id'    => ['sometimes', 'nullable', 'uuid'],
            'cover_photo_id'      => ['sometimes', 'nullable', 'uuid'],
        ];
    }
}
