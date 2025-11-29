<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CoinLedgerResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'transaction_id' => $this->transaction_id,
            'user_id' => $this->user_id,
            'amount' => (int) $this->amount,
            'balance_after' => (int) $this->balance_after,
            'activity_id' => $this->activity_id,
            'reference' => $this->reference,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at,
        ];
    }
}
