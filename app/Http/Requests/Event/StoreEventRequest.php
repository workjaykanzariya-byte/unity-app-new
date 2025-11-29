<?php

namespace App\Http\Requests\Event;

use Illuminate\Foundation\Http\FormRequest;

class StoreEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'circle_id' => 'required|uuid|exists:circles,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_at' => 'required|date',
            'end_at' => 'nullable|date|after_or_equal:start_at',
            'is_virtual' => 'sometimes|boolean',
            'location_text' => 'sometimes|nullable|string',
            'agenda' => 'sometimes|nullable|string',
            'speakers' => 'sometimes|array',
            'speakers.*' => 'array',
            'banner_url' => 'sometimes|nullable|url|max:2000',
            'visibility' => 'required|string|in:public,circle,connections',
            'is_paid' => 'sometimes|boolean',
            'metadata' => 'sometimes|array',
        ];
    }
}
