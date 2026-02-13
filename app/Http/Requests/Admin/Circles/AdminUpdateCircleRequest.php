<?php

namespace App\Http\Requests\Admin\Circles;

use App\Models\Circle;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class AdminUpdateCircleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'purpose' => ['nullable', 'string'],
            'announcement' => ['nullable', 'string'],
            'city_id' => ['required', 'uuid', 'exists:cities,id'],
            'founder_user_id' => ['required', 'uuid', 'exists:users,id'],
            'director_user_id' => ['nullable', 'uuid', 'exists:users,id'],
            'industry_director_user_id' => ['nullable', 'uuid', 'exists:users,id'],
            'ded_user_id' => ['nullable', 'uuid', 'exists:users,id'],
            'type' => ['required', Rule::in(Circle::TYPE_OPTIONS)],
            'status' => ['required', Rule::in(Circle::STATUS_OPTIONS)],
            'stage' => ['nullable', Rule::in(Circle::STAGE_OPTIONS)],
            'meeting_mode' => ['nullable', Rule::in(Circle::MEETING_MODE_OPTIONS)],
            'meeting_frequency' => ['nullable', Rule::in(Circle::MEETING_FREQUENCY_OPTIONS)],
            'meeting_repeat' => ['nullable', 'string', 'max:500'],
            'launch_date' => ['nullable', 'date'],
            'annual_fee' => ['nullable', 'integer', 'min:0'],
            'industry_tags' => ['nullable'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('founder_user_id')) {
            $admin = Auth::guard('admin')->user();
            $defaultFounder = $admin ? User::query()->where('email', $admin->email)->first() : null;

            if ($defaultFounder) {
                $this->merge(['founder_user_id' => $defaultFounder->id]);
            }
        }
    }
}
