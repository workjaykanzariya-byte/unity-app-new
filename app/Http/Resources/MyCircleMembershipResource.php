<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MyCircleMembershipResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $expiresAt = $this->paid_ends_at;

        return [
            'membership_id' => $this->id,
            'circle_id' => $this->circle_id,
            'role' => $this->role,
            'status' => $this->status,
            'joined_at' => $this->joined_at,
            'joined_via' => $this->joined_via,
            'joined_via_payment' => $this->joined_via_payment,
            'payment_id' => $this->payment_id,
            'payment_status' => $this->payment_status,
            'billing_term' => $this->billing_term,
            'paid_at' => $this->paid_at,
            'paid_starts_at' => $this->paid_starts_at,
            'paid_ends_at' => $expiresAt,
            'zoho_subscription_id' => $this->zoho_subscription_id,
            'zoho_addon_code' => $this->zoho_addon_code,
            'meta' => $this->meta,
            'membership_started_at' => $this->paid_starts_at ?? $this->joined_at,
            'membership_expires_at' => $expiresAt,
            'is_active' => is_null($this->left_at) && is_null($this->deleted_at),
            'is_expired' => $expiresAt ? $expiresAt->isPast() : false,
            'circle' => $this->whenLoaded('circle', function () {
                if (! $this->circle) {
                    return null;
                }

                return new CircleResource($this->circle);
            }),
        ];
    }
}
