<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ActivityResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'related_user_id' => $this->related_user_id,
            'circle_id' => $this->circle_id,
            'event_id' => $this->event_id,
            'type' => $this->type,
            'status' => $this->status,
            'description' => $this->description,
            'admin_notes' => $this->admin_notes,
            'requires_verification' => (bool) $this->requires_verification,
            'verified_by_admin_id' => $this->verified_by_admin_id,
            'verified_at' => $this->verified_at,
            'coins_awarded' => (int) $this->coins_awarded,
            'coins_ledger_id' => $this->coins_ledger_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'display_name' => $this->user->display_name,
                    'first_name' => $this->user->first_name,
                    'last_name' => $this->user->last_name,
                    'profile_photo_url' => $this->user->profile_photo_url,
                ];
            }),
            'related_user' => $this->whenLoaded('relatedUser', function () {
                return [
                    'id' => $this->relatedUser->id,
                    'display_name' => $this->relatedUser->display_name,
                ];
            }),
            'circle' => $this->whenLoaded('circle', function () {
                return [
                    'id' => $this->circle->id,
                    'name' => $this->circle->name,
                    'slug' => $this->circle->slug,
                ];
            }),
            'event' => $this->whenLoaded('event', function () {
                return [
                    'id' => $this->event->id,
                    'title' => $this->event->title,
                    'start_at' => $this->event->start_at,
                ];
            }),
        ];
    }
}
