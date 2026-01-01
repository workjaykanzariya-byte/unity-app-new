<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class UserMiniResource extends JsonResource
{
    public function toArray($request): array
    {
        $user = $this->resource;

        $displayName = $user->display_name
            ?? trim(($user->first_name ?? '').' '.($user->last_name ?? ''));

        if (empty($displayName) && ! empty($user->email)) {
            $displayName = Str::before($user->email, '@');
        }

        return [
            'id' => $user->id,
            'display_name' => $displayName ? trim($displayName) : null,
            'profile_photo_url' => $this->buildProfilePhotoUrl(),
        ];
    }

    private function buildProfilePhotoUrl(): ?string
    {
        $user = $this->resource;

        $fileId = $user->profile_photo_file_id
            ?? $user->profile_photo_id
            ?? null;

        if (! $fileId) {
            return null;
        }

        return url('/api/v1/files/'.$fileId);
    }
}
