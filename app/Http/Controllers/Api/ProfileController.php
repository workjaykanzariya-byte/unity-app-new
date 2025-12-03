<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Profile\StoreUserLinkRequest;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Http\Requests\Profile\UpdateUserLinkRequest;
use App\Http\Resources\UserLinkResource;
use App\Http\Resources\UserProfileResource;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class ProfileController extends BaseApiController
{
    public function show(Request $request)
    {
        $user = $request->user()->load([
            'profilePhotoFile',
            'coverPhotoFile',
            'userLinks',
        ]);

        return $this->success(new UserProfileResource($user));
    }

    public function update(UpdateProfileRequest $request)
    {
        $user = $request->user();
        $data = $request->validated();

        $mapped = [];

        $fields = [
            'first_name' => 'first_name',
            'last_name' => 'last_name',
            'company_name' => 'company_name',
            'designation' => 'designation',
            'about' => 'short_bio',
            'gender' => 'gender',
            'dob' => 'dob',
            'experience_years' => 'experience_years',
            'experience_summary' => 'experience_summary',
            'city_id' => 'city_id',
            'city' => 'city',
            'skills' => 'skills',
            'interests' => 'interests',
            'social_links' => 'social_links',
            'profile_photo_id' => 'profile_photo_file_id',
            'cover_photo_id' => 'cover_photo_file_id',
        ];

        foreach ($fields as $input => $column) {
            if (Arr::exists($data, $input)) {
                $mapped[$column] = $data[$input];
            }
        }

        if (Arr::exists($mapped, 'first_name') || Arr::exists($mapped, 'last_name')) {
            $mapped['display_name'] = trim(($mapped['first_name'] ?? $user->first_name ?? '') . ' ' . ($mapped['last_name'] ?? $user->last_name ?? ''))
                ?: $user->email;
        }

        DB::transaction(function () use ($user, $mapped): void {
            $user->forceFill($mapped);
            $user->saveOrFail();
        });

        $user->refresh()->load(['profilePhotoFile', 'coverPhotoFile', 'userLinks']);

        return $this->success(new UserProfileResource($user), 'Profile updated successfully');
    }

    public function links(Request $request)
    {
        $user = $request->user();
        $links = $user->userLinks()->orderByDesc('created_at')->get();

        return $this->success(UserLinkResource::collection($links));
    }

    public function storeLink(StoreUserLinkRequest $request)
    {
        $user = $request->user();
        $data = $request->validated();

        $link = $user->userLinks()->create($data);

        return $this->success(new UserLinkResource($link), 'Link created successfully', 201);
    }

    public function updateLink(UpdateUserLinkRequest $request, string $id)
    {
        $user = $request->user();
        $data = $request->validated();

        $link = $user->userLinks()->where('id', $id)->first();

        if (! $link) {
            return $this->error('Link not found', 404);
        }

        $link->fill($data);
        $link->save();

        return $this->success(new UserLinkResource($link), 'Link updated successfully');
    }

    public function destroyLink(Request $request, string $id)
    {
        $user = $request->user();
        $link = $user->userLinks()->where('id', $id)->first();

        if (! $link) {
            return $this->error('Link not found', 404);
        }

        $link->delete();

        return $this->success(null, 'Link deleted successfully');
    }
}
