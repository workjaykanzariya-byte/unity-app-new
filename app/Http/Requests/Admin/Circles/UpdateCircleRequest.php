<?php

namespace App\Http\Requests\Admin\Circles;

use App\Models\Circle;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdateCircleRequest extends FormRequest
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
            'type' => ['required', Rule::in(Circle::TYPE_OPTIONS)],
            'status' => ['required', Rule::in(Circle::STATUS_OPTIONS)],
            'industry_tags' => ['nullable'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('founder_user_id')) {
            return;
        }

        $admin = Auth::guard('admin')->user();

        if (! $admin) {
            return;
        }

        $defaultFounder = User::query()->where('email', $admin->email)->first();

        if ($defaultFounder) {
            $this->merge([
                'founder_user_id' => $defaultFounder->id,
            ]);
        }
    }
}
