<?php

namespace App\Http\Requests\CoinClaims;

use Illuminate\Foundation\Http\FormRequest;

class RejectCoinClaimRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
