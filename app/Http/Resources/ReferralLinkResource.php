<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ReferralLinkResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'referrer_user_id' => $this->referrer_user_id,
            'token' => $this->token,
            'status' => $this->status,
            'stats' => $this->stats,
            'expires_at' => $this->expires_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'visitors_count' => $this->when(isset($this->visitors_count), (int) $this->visitors_count),
        ];
    }
}
