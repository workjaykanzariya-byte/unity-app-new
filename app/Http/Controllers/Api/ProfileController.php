<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Profile\StoreUserLinkRequest;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Http\Requests\Profile\UpdateUserLinkRequest;
use App\Http\Resources\UserLinkResource;
use App\Http\Resources\UserProfileResource;
use Illuminate\Http\Request;

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

        $fieldMappings = [
            'first_name' => 'first_name',
            'last_name' => 'last_name',
            'company_name' => 'company_name',
            'designation' => 'designation',
            'gender' => 'gender',
            'dob' => 'dob',
            'experience_years' => 'experience_years',
            'experience_summary' => 'experience_summary',
            'skills' => 'skills',
            'interests' => 'interests',
            'social_links' => 'social_links',
            'city_id' => 'city_id',
            'profile_photo_id' => 'profile_photo_file_id',
            'cover_photo_id' => 'cover_photo_file_id',
        ];

        foreach ($fieldMappings as $requestKey => $userAttribute) {
            if (array_key_exists($requestKey, $data)) {
                $user->{$userAttribute} = $data[$requestKey];
            }
        }

        if (array_key_exists('about', $data)) {
            $user->short_bio = $data['about'];
        }

        if (array_key_exists('first_name', $data) || array_key_exists('last_name', $data)) {
            $user->display_name = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: $user->email;
        }

        $user->save();

        $user->load(['city', 'userLinks', 'profilePhotoFile', 'coverPhotoFile']);

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
