<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReferralMemberResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $history = $this->referralHistoryAsReferred;

        return [
            'id' => (string) $this->id,
            'name' => (string) ($this->display_name ?? trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? ''))),
            'email' => $this->email,
            'company_name' => $this->company_name,
            'mobile' => $this->phone,
            'profile_image' => $this->profile_photo_url,
            'registered_at' => optional($this->created_at)->toISOString(),
            'referral_code_used' => $this->referral_code_used,
            'reward_coins' => (int) ($history->reward_coins ?? 0),
        ];
    }
}

