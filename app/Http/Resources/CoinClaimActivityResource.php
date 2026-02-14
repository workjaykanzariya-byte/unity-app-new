<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CoinClaimActivityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'code' => $this['code'],
            'label' => $this['label'],
            'coins' => (int) config('coins.claim_coin.'.$this['code'], 0),
            'fields' => $this['fields'],
        ];
    }
}
