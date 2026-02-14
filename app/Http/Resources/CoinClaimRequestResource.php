<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CoinClaimRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'activity_code' => $this->activity_code,
            'activity_label' => collect(config('coins.claim_coin_labels', []))->get($this->activity_code, $this->activity_code),
            'payload' => $this->payload,
            'status' => $this->status,
            'coins_awarded' => $this->coins_awarded,
            'admin_note' => $this->admin_note,
            'reviewed_by_admin_id' => $this->reviewed_by_admin_id,
            'reviewed_at' => $this->reviewed_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'display_name' => $this->user->display_name,
                'first_name' => $this->user->first_name,
                'last_name' => $this->user->last_name,
                'phone' => $this->user->phone,
            ]),
        ];
    }
}
