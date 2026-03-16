<?php

namespace App\Http\Requests\Admin\Circulars;

use App\Models\Circular;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCircularRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'send_push_notification' => $this->boolean('send_push_notification'),
            'allow_comments' => $this->boolean('allow_comments'),
            'is_pinned' => $this->boolean('is_pinned'),
        ]);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'summary' => ['required', 'string', 'max:500'],
            'category' => ['required', Rule::in(Circular::CATEGORY_OPTIONS)],
            'priority' => ['required', Rule::in(Circular::PRIORITY_OPTIONS)],
            'publish_date' => ['required', 'date'],
            'expiry_date' => ['nullable', 'date', 'after_or_equal:publish_date'],
            'content' => ['required', 'string'],
            'video_url' => ['nullable', 'url', 'max:1000'],
            'cta_label' => ['nullable', 'string', 'max:100', 'required_with:cta_url'],
            'cta_url' => ['nullable', 'url', 'max:1000', 'required_with:cta_label'],
            'audience_type' => ['required', Rule::in(Circular::AUDIENCE_OPTIONS)],
            'city_id' => ['nullable', 'exists:cities,id'],
            'circle_id' => ['nullable', 'exists:circles,id'],
            'featured_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png,webp', 'max:10240'],
            'send_push_notification' => ['nullable', 'boolean'],
            'allow_comments' => ['nullable', 'boolean'],
            'is_pinned' => ['nullable', 'boolean'],
            'status' => ['required', Rule::in(Circular::statusOptions())],
        ];
    }
}
