<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReportPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason_id' => [
                'required',
                'integer',
                Rule::exists('post_report_reasons', 'id')->where('is_active', true),
            ],
            'reason' => ['prohibited'],
            'note' => ['prohibited'],
            'message' => ['prohibited'],
        ];
    }
}
