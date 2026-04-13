<?php

namespace App\Http\Requests\Impacts;

use App\Services\Impacts\LifeImpactActionCatalog;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLifeImpactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('date')) {
            $this->merge(['date' => now()->toDateString()]);
        }

        if ($this->has('remarks')) {
            $this->merge(['remarks' => trim((string) $this->input('remarks'))]);
        }
    }

    public function rules(): array
    {
        $keys = app(LifeImpactActionCatalog::class)->keys();

        return [
            'action_key' => ['required', 'string', Rule::in($keys)],
            'remarks' => ['nullable', 'string', 'max:5000'],
            'date' => ['required', 'date'],
            'impacted_peer_id' => ['nullable', 'uuid', 'exists:users,id'],
        ];
    }

    protected function failedValidation(ValidatorContract $validator): void
    {
        throw new \Illuminate\Http\Exceptions\HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validation failed.',
            'errors' => $validator->errors(),
        ], 422));
    }
}
